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
 * @internal
 */
final class FormulaLexer
{
    public static function assertAllowedExpression(string $expression): void
    {
        $expression = trim($expression);

        if ($expression === '') {
            throw new InvalidArgumentException('Formula expression cannot be empty.');
        }

        $withoutRefs = preg_replace(FormulaCatalog::FIELD_REF_PATTERN, '0', $expression);

        if (! is_string($withoutRefs)) {
            throw new InvalidArgumentException('Formula contains disallowed characters or constructs.');
        }

        $withoutFunctions = preg_replace(
            '/\b(?:'.implode('|', FormulaCatalog::ALLOWED_FUNCTIONS).')\s*\(/',
            '(',
            $withoutRefs,
        );

        if (! is_string($withoutFunctions)) {
            throw new InvalidArgumentException('Formula contains disallowed characters or constructs.');
        }

        $withoutComparisons = preg_replace('/[<>!=]=?/', ' ', $withoutFunctions);

        if (! is_string($withoutComparisons) || ! preg_match('#^[\d\s+\-*/%().,]+$#', $withoutComparisons)) {
            throw new InvalidArgumentException('Formula contains disallowed characters or constructs.');
        }
    }

    /**
     * @return list<string>
     */
    public static function tokenize(string $expression): array
    {
        $expression = preg_replace('/\s+/', '', $expression);

        if (! is_string($expression) || $expression === '') {
            throw new InvalidArgumentException('Formula expression cannot be empty.');
        }

        $tokens = [];
        $length = strlen($expression);

        for ($i = 0; $i < $length; $i++) {
            $char = $expression[$i];
            $next = $expression[$i + 1] ?? '';

            if (ctype_digit($char) || ($char === '.' && $next !== '' && ctype_digit($next))) {
                $start = $i;

                while ($i < $length && (ctype_digit($expression[$i]) || $expression[$i] === '.')) {
                    $i++;
                }

                $number = substr($expression, $start, $i - $start);
                $i--;

                if (! is_numeric($number)) {
                    throw new InvalidArgumentException('Invalid numeric literal in formula expression.');
                }

                $tokens[] = $number;

                continue;
            }

            if (ctype_alpha($char)) {
                $start = $i;

                while ($i < $length && ctype_alpha($expression[$i])) {
                    $i++;
                }

                $name = substr($expression, $start, $i - $start);
                $i--;

                if (! in_array($name, FormulaCatalog::ALLOWED_FUNCTIONS, true)) {
                    throw new InvalidArgumentException('Formula contains disallowed characters or constructs.');
                }

                $tokens[] = $name;

                continue;
            }

            if (in_array($char.$next, ['>=', '<=', '==', '!='], true)) {
                $tokens[] = $char.$next;
                $i++;

                continue;
            }

            if (in_array($char, ['+', '-', '*', '/', '%', '(', ')', ',', '>', '<'], true)) {
                $tokens[] = $char;

                continue;
            }

            throw new InvalidArgumentException('Formula contains disallowed characters or constructs.');
        }

        if ($tokens === []) {
            throw new InvalidArgumentException('Formula expression cannot be empty.');
        }

        return $tokens;
    }

    /**
     * @return list<string>
     */
    public static function fieldReferences(string $expression): array
    {
        preg_match_all(FormulaCatalog::FIELD_REF_PATTERN, $expression, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * @return list<string>
     */
    public static function functionsUsed(string $expression): array
    {
        preg_match_all(
            '/\b('.implode('|', FormulaCatalog::ALLOWED_FUNCTIONS).')\s*\(/',
            $expression,
            $matches,
        );

        $functions = array_values(array_unique($matches[1] ?? []));
        sort($functions);

        return $functions;
    }
}
