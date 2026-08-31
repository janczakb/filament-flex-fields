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
 * Eager builtins invoked after arguments are evaluated.
 *
 * @internal
 */
final class FormulaFunctions
{
    /**
     * @param  list<float>  $arguments
     */
    public static function call(string $name, array $arguments): float
    {
        return match ($name) {
            'abs' => self::unary($name, $arguments, static fn (float $value): float => abs($value)),
            'floor' => self::unary($name, $arguments, static fn (float $value): float => floor($value)),
            'ceil' => self::unary($name, $arguments, static fn (float $value): float => ceil($value)),
            'trunc' => self::unary($name, $arguments, static fn (float $value): float => (float) ((int) $value)),
            'pct' => self::unary($name, $arguments, static fn (float $value): float => $value / 100),
            'sign' => self::unary($name, $arguments, static function (float $value): float {
                if ($value > 0) {
                    return 1.0;
                }

                if ($value < 0) {
                    return -1.0;
                }

                return 0.0;
            }),
            'not' => self::unary($name, $arguments, static fn (float $value): float => $value == 0.0 ? 1.0 : 0.0),
            'sqrt' => self::sqrt($arguments),
            'round' => self::round($arguments),
            'min' => self::variadic($name, $arguments, static fn (float ...$values): float => min($values)),
            'max' => self::variadic($name, $arguments, static fn (float ...$values): float => max($values)),
            'sum' => self::variadic($name, $arguments, static fn (float ...$values): float => array_sum($values)),
            'avg' => self::variadic($name, $arguments, static fn (float ...$values): float => array_sum($values) / count($values)),
            'clamp' => self::clamp($arguments),
            'pow' => self::pow($arguments),
            'between' => self::between($arguments),
            default => throw new InvalidArgumentException("Unknown formula function [{$name}]."),
        };
    }

    /**
     * @param  list<float>  $arguments
     * @param  callable(float): float  $callback
     */
    private static function unary(string $name, array $arguments, callable $callback): float
    {
        if (count($arguments) !== 1) {
            throw new InvalidArgumentException("Function [{$name}] expects exactly 1 argument.");
        }

        return $callback($arguments[0]);
    }

    /**
     * @param  list<float>  $arguments
     * @param  callable(float...): float  $callback
     */
    private static function variadic(string $name, array $arguments, callable $callback): float
    {
        if (count($arguments) < 2) {
            throw new InvalidArgumentException("Function [{$name}] expects at least 2 arguments.");
        }

        return $callback(...$arguments);
    }

    /**
     * @param  list<float>  $arguments
     */
    private static function round(array $arguments): float
    {
        $count = count($arguments);

        if ($count === 1) {
            return round($arguments[0]);
        }

        if ($count === 2) {
            $precision = (int) $arguments[1];

            if ($precision < 0 || $precision > 12) {
                throw new InvalidArgumentException('Function [round] precision must be between 0 and 12.');
            }

            return round($arguments[0], $precision);
        }

        throw new InvalidArgumentException('Function [round] expects 1 or 2 arguments.');
    }

    /**
     * @param  list<float>  $arguments
     */
    private static function clamp(array $arguments): float
    {
        if (count($arguments) !== 3) {
            throw new InvalidArgumentException('Function [clamp] expects exactly 3 arguments: value, min, max.');
        }

        [$value, $min, $max] = $arguments;

        if ($min > $max) {
            throw new InvalidArgumentException('Function [clamp] requires min <= max.');
        }

        return max($min, min($max, $value));
    }

    /**
     * @param  list<float>  $arguments
     */
    private static function between(array $arguments): float
    {
        if (count($arguments) !== 3) {
            throw new InvalidArgumentException('Function [between] expects exactly 3 arguments: value, min, max.');
        }

        [$value, $min, $max] = $arguments;

        if ($min > $max) {
            throw new InvalidArgumentException('Function [between] requires min <= max.');
        }

        return ($value >= $min && $value <= $max) ? 1.0 : 0.0;
    }

    /**
     * @param  list<float>  $arguments
     */
    private static function pow(array $arguments): float
    {
        if (count($arguments) !== 2) {
            throw new InvalidArgumentException('Function [pow] expects exactly 2 arguments.');
        }

        [$base, $exponent] = $arguments;

        if ($exponent < -12 || $exponent > 12) {
            throw new InvalidArgumentException('Function [pow] exponent must be between -12 and 12.');
        }

        return $base ** $exponent;
    }

    /**
     * @param  list<float>  $arguments
     */
    private static function sqrt(array $arguments): float
    {
        if (count($arguments) !== 1) {
            throw new InvalidArgumentException('Function [sqrt] expects exactly 1 argument.');
        }

        if ($arguments[0] < 0) {
            throw new InvalidArgumentException('Function [sqrt] does not allow negative values.');
        }

        return sqrt($arguments[0]);
    }
}
