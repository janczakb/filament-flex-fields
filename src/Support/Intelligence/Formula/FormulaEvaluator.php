<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Support\Intelligence\Formula;

use InvalidArgumentException;

/**
 * AST walker with short-circuit evaluation for control-flow helpers.
 *
 * @internal
 */
final class FormulaEvaluator
{
    /**
     * @param  array{0: 'lit'|'bin'|'un'|'call', 1?: mixed, 2?: mixed, 3?: mixed}  $ast
     */
    public static function evaluate(array $ast): float|int
    {
        return self::normalize(self::evaluateAst($ast));
    }

    /**
     * @param  array{0: 'lit'|'bin'|'un'|'call', 1?: mixed, 2?: mixed, 3?: mixed}  $ast
     */
    public static function evaluateAst(array $ast): float
    {
        return match ($ast[0]) {
            'lit' => (float) $ast[1],
            'un' => self::evaluateUnary((string) $ast[1], $ast[2]),
            'bin' => self::evaluateBinary((string) $ast[1], $ast[2], $ast[3]),
            'call' => self::evaluateCall((string) $ast[1], $ast[2]),
            default => throw new InvalidArgumentException('Unexpected formula AST node.'),
        };
    }

    /**
     * @param  array{0: 'lit'|'bin'|'un'|'call', 1?: mixed, 2?: mixed, 3?: mixed}  $child
     */
    private static function evaluateUnary(string $operator, array $child): float
    {
        $value = self::evaluateAst($child);

        return match ($operator) {
            '-' => -$value,
            default => throw new InvalidArgumentException('Unexpected unary operator.'),
        };
    }

    /**
     * @param  array{0: 'lit'|'bin'|'un'|'call', 1?: mixed, 2?: mixed, 3?: mixed}  $left
     * @param  array{0: 'lit'|'bin'|'un'|'call', 1?: mixed, 2?: mixed, 3?: mixed}  $right
     */
    private static function evaluateBinary(string $operator, array $left, array $right): float
    {
        $leftValue = self::evaluateAst($left);
        $rightValue = self::evaluateAst($right);

        return match ($operator) {
            '+' => $leftValue + $rightValue,
            '-' => $leftValue - $rightValue,
            '*' => $leftValue * $rightValue,
            '/' => self::safeDivide($leftValue, $rightValue),
            '%' => self::safeModulo($leftValue, $rightValue),
            '>' => $leftValue > $rightValue ? 1.0 : 0.0,
            '<' => $leftValue < $rightValue ? 1.0 : 0.0,
            '>=' => $leftValue >= $rightValue ? 1.0 : 0.0,
            '<=' => $leftValue <= $rightValue ? 1.0 : 0.0,
            '==' => $leftValue == $rightValue ? 1.0 : 0.0,
            '!=' => $leftValue != $rightValue ? 1.0 : 0.0,
            default => throw new InvalidArgumentException('Unexpected binary operator.'),
        };
    }

    /**
     * @param  list<array{0: 'lit'|'bin'|'un'|'call', 1?: mixed, 2?: mixed, 3?: mixed}>  $arguments
     */
    private static function evaluateCall(string $name, array $arguments): float
    {
        if (in_array($name, FormulaCatalog::SHORT_CIRCUIT_FUNCTIONS, true)) {
            return self::evaluateShortCircuit($name, $arguments);
        }

        $values = array_map(
            static fn (array $argument): float => self::evaluateAst($argument),
            $arguments,
        );

        return FormulaFunctions::call($name, $values);
    }

    /**
     * @param  list<array{0: 'lit'|'bin'|'un'|'call', 1?: mixed, 2?: mixed, 3?: mixed}>  $arguments
     */
    private static function evaluateShortCircuit(string $name, array $arguments): float
    {
        return match ($name) {
            'if' => self::evaluateIf($arguments),
            'and' => self::evaluateAnd($arguments),
            'or' => self::evaluateOr($arguments),
            'coalesce', 'nz' => self::evaluateCoalesce($arguments),
            default => throw new InvalidArgumentException("Unknown short-circuit formula function [{$name}]."),
        };
    }

    /**
     * @param  list<array{0: 'lit'|'bin'|'un'|'call', 1?: mixed, 2?: mixed, 3?: mixed}>  $arguments
     */
    private static function evaluateIf(array $arguments): float
    {
        if (count($arguments) !== 3) {
            throw new InvalidArgumentException('Function [if] expects exactly 3 arguments: condition, whenTrue, whenFalse.');
        }

        $condition = self::evaluateAst($arguments[0]);

        return $condition != 0.0
            ? self::evaluateAst($arguments[1])
            : self::evaluateAst($arguments[2]);
    }

    /**
     * @param  list<array{0: 'lit'|'bin'|'un'|'call', 1?: mixed, 2?: mixed, 3?: mixed}>  $arguments
     */
    private static function evaluateAnd(array $arguments): float
    {
        if (count($arguments) < 2) {
            throw new InvalidArgumentException('Function [and] expects at least 2 arguments.');
        }

        foreach ($arguments as $argument) {
            if (self::evaluateAst($argument) == 0.0) {
                return 0.0;
            }
        }

        return 1.0;
    }

    /**
     * @param  list<array{0: 'lit'|'bin'|'un'|'call', 1?: mixed, 2?: mixed, 3?: mixed}>  $arguments
     */
    private static function evaluateOr(array $arguments): float
    {
        if (count($arguments) < 2) {
            throw new InvalidArgumentException('Function [or] expects at least 2 arguments.');
        }

        foreach ($arguments as $argument) {
            if (self::evaluateAst($argument) != 0.0) {
                return 1.0;
            }
        }

        return 0.0;
    }

    /**
     * @param  list<array{0: 'lit'|'bin'|'un'|'call', 1?: mixed, 2?: mixed, 3?: mixed}>  $arguments
     */
    private static function evaluateCoalesce(array $arguments): float
    {
        if ($arguments === []) {
            throw new InvalidArgumentException('Function [coalesce] expects at least 1 argument.');
        }

        $last = 0.0;

        foreach ($arguments as $argument) {
            $last = self::evaluateAst($argument);

            if ($last != 0.0) {
                return $last;
            }
        }

        return $last;
    }

    private static function safeDivide(float $left, float $right): float
    {
        if ($right == 0.0) {
            throw new InvalidArgumentException('Division by zero in formula expression.');
        }

        return $left / $right;
    }

    private static function safeModulo(float $left, float $right): float
    {
        if ($right == 0.0) {
            throw new InvalidArgumentException('Modulo by zero in formula expression.');
        }

        return fmod($left, $right);
    }

    private static function normalize(float $value): float|int
    {
        if (! is_finite($value)) {
            throw new InvalidArgumentException('Formula result must be a finite number.');
        }

        if (floor($value) === $value && abs($value) <= PHP_INT_MAX) {
            return (int) $value;
        }

        return $value;
    }
}
