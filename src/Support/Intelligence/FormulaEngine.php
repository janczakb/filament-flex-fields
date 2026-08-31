<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Support\Intelligence;

use Bjanczak\FilamentFlexFields\Support\Intelligence\Formula\FormulaCatalog;
use Bjanczak\FilamentFlexFields\Support\Intelligence\Formula\FormulaEvaluator;
use Bjanczak\FilamentFlexFields\Support\Intelligence\Formula\FormulaGraph;
use Bjanczak\FilamentFlexFields\Support\Intelligence\Formula\FormulaLexer;
use Bjanczak\FilamentFlexFields\Support\Intelligence\Formula\FormulaParser;
use InvalidArgumentException;

/**
 * Enterprise whitelist-only formula engine for Filament / flex schemas.
 *
 * Allowed:
 * - numbers, `+ - * / % ( )`, comparisons `> < >= <= == !=`
 * - field refs `{field_key}` (scalars or CurrencyField `{amount,currency}` / minor-unit ints)
 * - functions: abs, min, max, round, floor, ceil, trunc, clamp, sum, avg, pct, pow, sqrt,
 *   sign, between, and, or, not, coalesce, if, nz
 * - short-circuit evaluation for `if` / `and` / `or` / `coalesce` / `nz`
 *
 * Not allowed: arbitrary identifiers, JS/PHP calls, side effects.
 */
final class FormulaEngine
{
    /**
     * @param  array<string, mixed>  $values
     */
    public static function evaluate(string $expression, array $values, bool $nullAsZero = false): float|int
    {
        FormulaLexer::assertAllowedExpression($expression);

        $numericExpression = self::substituteFieldReferences($expression, $values, $nullAsZero);

        return self::evaluateNumericExpression($numericExpression);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function tryEvaluate(string $expression, array $values, bool $nullAsZero = false): float|int|null
    {
        try {
            return self::evaluate($expression, $values, $nullAsZero);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    public static function validate(string $expression): array
    {
        try {
            FormulaLexer::assertAllowedExpression($expression);
            FormulaParser::parse(
                FormulaLexer::tokenize(
                    (string) preg_replace(FormulaCatalog::FIELD_REF_PATTERN, '0', $expression),
                ),
            );

            return [];
        } catch (InvalidArgumentException $exception) {
            return [$exception->getMessage()];
        }
    }

    /**
     * Evaluate an acyclic formula map in dependency order.
     *
     * @param  array<string, string>  $formulas  field key => expression
     * @param  array<string, mixed>  $values  seed inputs
     * @return array<string, float|int> computed formula keys only
     */
    public static function evaluateMap(array $formulas, array $values, bool $nullAsZero = false): array
    {
        if ($formulas === []) {
            return [];
        }

        if (FormulaGraph::detectCycle($formulas) !== []) {
            throw new InvalidArgumentException('Cannot evaluate formula map with dependency cycles.');
        }

        $resolved = $values;
        $results = [];

        foreach (FormulaGraph::topologicalOrder($formulas) as $fieldKey) {
            $expression = $formulas[$fieldKey] ?? null;

            if (! is_string($expression)) {
                throw new InvalidArgumentException('Formula map values must be strings.');
            }

            $result = self::evaluate($expression, $resolved, $nullAsZero);
            $results[$fieldKey] = $result;
            $resolved[$fieldKey] = $result;
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{
     *     expression: string,
     *     references: list<string>,
     *     substituted: string,
     *     result: float|int,
     *     functions: list<string>
     * }
     */
    public static function explain(string $expression, array $values, bool $nullAsZero = false): array
    {
        FormulaLexer::assertAllowedExpression($expression);

        $substituted = self::substituteFieldReferences($expression, $values, $nullAsZero);

        return [
            'expression' => $expression,
            'references' => self::fieldReferences($expression),
            'substituted' => $substituted,
            'result' => self::evaluateNumericExpression($substituted),
            'functions' => self::functionsUsed($expression),
        ];
    }

    /**
     * Normalize form / CurrencyField state to a float.
     *
     * CurrencyField minor-unit ints and `{amount: int, currency: string}` are returned as raw amounts
     * (minor units). Use {@see moneyMajor()} when you need major units.
     */
    public static function toNumber(mixed $value, bool $nullAsZero = false): float
    {
        if ($value === null || $value === '') {
            if ($nullAsZero) {
                return 0.0;
            }

            throw new InvalidArgumentException('Formula value must be numeric.');
        }

        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_array($value) && array_key_exists('amount', $value)) {
            if ($value['amount'] === null || $value['amount'] === '') {
                if ($nullAsZero) {
                    return 0.0;
                }

                throw new InvalidArgumentException('Formula currency amount must be numeric.');
            }

            if (! is_numeric($value['amount'])) {
                throw new InvalidArgumentException('Formula currency amount must be numeric.');
            }

            return (float) $value['amount'];
        }

        throw new InvalidArgumentException('Formula value must be numeric.');
    }

    /**
     * Convert CurrencyField-style minor units to major units.
     */
    public static function moneyMajor(mixed $value, int $fractionDigits = 2, bool $nullAsZero = false): float
    {
        if ($fractionDigits < 0 || $fractionDigits > 6) {
            throw new InvalidArgumentException('Money fraction digits must be between 0 and 6.');
        }

        $scale = 10 ** $fractionDigits;

        return self::toNumber($value, $nullAsZero) / $scale;
    }

    /**
     * Convert major units to CurrencyField minor units.
     */
    public static function moneyMinor(float|int $major, int $fractionDigits = 2): int
    {
        if ($fractionDigits < 0 || $fractionDigits > 6) {
            throw new InvalidArgumentException('Money fraction digits must be between 0 and 6.');
        }

        $scale = 10 ** $fractionDigits;

        return (int) round(((float) $major) * $scale);
    }

    /**
     * @return list<string>
     */
    public static function allowedFunctions(): array
    {
        return FormulaCatalog::ALLOWED_FUNCTIONS;
    }

    /**
     * @deprecated Formulas are always enabled; kept for backward compatibility.
     */
    public static function formulasEnabled(): bool
    {
        return true;
    }

    /**
     * @param  array<string, string>  $formulas  field key => expression
     * @return list<string> cycle keys, or empty when acyclic
     */
    public static function detectCycle(array $formulas): array
    {
        return FormulaGraph::detectCycle($formulas);
    }

    /**
     * @return list<string>
     */
    public static function fieldReferences(string $expression): array
    {
        return FormulaLexer::fieldReferences($expression);
    }

    /**
     * @return list<string>
     */
    public static function functionsUsed(string $expression): array
    {
        return FormulaLexer::functionsUsed($expression);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private static function substituteFieldReferences(string $expression, array $values, bool $nullAsZero): string
    {
        return (string) preg_replace_callback(
            FormulaCatalog::FIELD_REF_PATTERN,
            static function (array $matches) use ($values, $nullAsZero): string {
                $fieldKey = $matches[1];

                if (! array_key_exists($fieldKey, $values)) {
                    if ($nullAsZero) {
                        return '0';
                    }

                    throw new InvalidArgumentException("Missing value for formula field [{$fieldKey}].");
                }

                try {
                    return (string) self::toNumber($values[$fieldKey], $nullAsZero);
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException("Formula field [{$fieldKey}] must be numeric.", 0, $exception);
                }
            },
            $expression,
        );
    }

    private static function evaluateNumericExpression(string $expression): float|int
    {
        return FormulaEvaluator::evaluate(
            FormulaParser::parse(FormulaLexer::tokenize($expression)),
        );
    }
}
