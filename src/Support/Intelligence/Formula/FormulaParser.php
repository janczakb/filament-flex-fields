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
 * Pratt-style recursive descent parser → lightweight AST tuples.
 *
 * @internal
 */
final class FormulaParser
{
    /**
     * @param  list<string>  $tokens
     * @return array{0: 'lit'|'bin'|'un'|'call', 1?: mixed, 2?: mixed, 3?: mixed}
     */
    public static function parse(array $tokens): array
    {
        $index = 0;
        $ast = self::parseComparison($tokens, $index);

        if ($index !== count($tokens)) {
            throw new InvalidArgumentException('Unexpected tokens in formula expression.');
        }

        return $ast;
    }

    /**
     * @param  list<string>  $tokens
     * @return array{0: 'lit'|'bin'|'un'|'call', 1?: mixed, 2?: mixed, 3?: mixed}
     */
    private static function parseComparison(array $tokens, int &$index): array
    {
        $left = self::parseAddSub($tokens, $index);

        while ($index < count($tokens) && in_array($tokens[$index], ['>', '<', '>=', '<=', '==', '!='], true)) {
            $operator = $tokens[$index];
            $index++;
            $right = self::parseAddSub($tokens, $index);
            $left = ['bin', $operator, $left, $right];
        }

        return $left;
    }

    /**
     * @param  list<string>  $tokens
     * @return array{0: 'lit'|'bin'|'un'|'call', 1?: mixed, 2?: mixed, 3?: mixed}
     */
    private static function parseAddSub(array $tokens, int &$index): array
    {
        $left = self::parseMulDiv($tokens, $index);

        while ($index < count($tokens) && in_array($tokens[$index], ['+', '-'], true)) {
            $operator = $tokens[$index];
            $index++;
            $right = self::parseMulDiv($tokens, $index);
            $left = ['bin', $operator, $left, $right];
        }

        return $left;
    }

    /**
     * @param  list<string>  $tokens
     * @return array{0: 'lit'|'bin'|'un'|'call', 1?: mixed, 2?: mixed, 3?: mixed}
     */
    private static function parseMulDiv(array $tokens, int &$index): array
    {
        $left = self::parseUnary($tokens, $index);

        while ($index < count($tokens) && in_array($tokens[$index], ['*', '/', '%'], true)) {
            $operator = $tokens[$index];
            $index++;
            $right = self::parseUnary($tokens, $index);
            $left = ['bin', $operator, $left, $right];
        }

        return $left;
    }

    /**
     * @param  list<string>  $tokens
     * @return array{0: 'lit'|'bin'|'un'|'call', 1?: mixed, 2?: mixed, 3?: mixed}
     */
    private static function parseUnary(array $tokens, int &$index): array
    {
        if ($index < count($tokens) && $tokens[$index] === '-') {
            $index++;

            return ['un', '-', self::parsePrimary($tokens, $index)];
        }

        if ($index < count($tokens) && $tokens[$index] === '+') {
            $index++;
        }

        return self::parsePrimary($tokens, $index);
    }

    /**
     * @param  list<string>  $tokens
     * @return array{0: 'lit'|'bin'|'un'|'call', 1?: mixed, 2?: mixed, 3?: mixed}
     */
    private static function parsePrimary(array $tokens, int &$index): array
    {
        if ($index >= count($tokens)) {
            throw new InvalidArgumentException('Unexpected end of formula expression.');
        }

        $token = $tokens[$index];

        if (in_array($token, FormulaCatalog::ALLOWED_FUNCTIONS, true)) {
            return self::parseFunctionCall($token, $tokens, $index);
        }

        if ($token === '(') {
            $index++;
            $value = self::parseComparison($tokens, $index);

            if ($index >= count($tokens) || $tokens[$index] !== ')') {
                throw new InvalidArgumentException('Unmatched parenthesis in formula expression.');
            }

            $index++;

            return $value;
        }

        if (is_numeric($token)) {
            $index++;

            return ['lit', (float) $token];
        }

        throw new InvalidArgumentException('Unexpected token in formula expression.');
    }

    /**
     * @param  list<string>  $tokens
     * @return array{0: 'call', 1: string, 2: list<array{0: 'lit'|'bin'|'un'|'call', 1?: mixed, 2?: mixed, 3?: mixed}>}
     */
    private static function parseFunctionCall(string $name, array $tokens, int &$index): array
    {
        $index++;

        if ($index >= count($tokens) || $tokens[$index] !== '(') {
            throw new InvalidArgumentException("Function [{$name}] requires parentheses.");
        }

        $index++;
        $arguments = [];

        if ($index < count($tokens) && $tokens[$index] !== ')') {
            $arguments[] = self::parseComparison($tokens, $index);

            while ($index < count($tokens) && $tokens[$index] === ',') {
                $index++;
                $arguments[] = self::parseComparison($tokens, $index);
            }
        }

        if ($index >= count($tokens) || $tokens[$index] !== ')') {
            throw new InvalidArgumentException("Unmatched parenthesis in function [{$name}].");
        }

        $index++;

        return ['call', $name, $arguments];
    }
}
