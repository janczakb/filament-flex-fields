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
 * Dependency cycle detection and topological evaluation order.
 *
 * @internal
 */
final class FormulaGraph
{
    /**
     * @param  array<string, string>  $formulas  field key => expression
     * @return list<string> cycle keys, or empty when acyclic
     */
    public static function detectCycle(array $formulas): array
    {
        $dependencies = [];

        foreach ($formulas as $fieldKey => $expression) {
            if (! is_string($fieldKey) || ! is_string($expression)) {
                throw new InvalidArgumentException('Formula map keys and values must be strings.');
            }

            $dependencies[$fieldKey] = FormulaLexer::fieldReferences($expression);
        }

        $visited = [];
        $stack = [];
        $cycleKeys = [];

        foreach (array_keys($dependencies) as $node) {
            if (! isset($visited[$node])) {
                self::visit($node, $dependencies, $visited, $stack, $cycleKeys);
            }
        }

        sort($cycleKeys);

        return array_values(array_unique($cycleKeys));
    }

    /**
     * @param  array<string, string>  $formulas
     * @return list<string>
     */
    public static function topologicalOrder(array $formulas): array
    {
        $dependencies = [];
        $remaining = [];

        foreach ($formulas as $fieldKey => $expression) {
            $refs = array_values(array_filter(
                FormulaLexer::fieldReferences($expression),
                static fn (string $ref): bool => array_key_exists($ref, $formulas),
            ));
            $dependencies[$fieldKey] = $refs;
            $remaining[$fieldKey] = count($refs);
        }

        $queue = [];

        foreach ($remaining as $fieldKey => $count) {
            if ($count === 0) {
                $queue[] = $fieldKey;
            }
        }

        $order = [];

        while ($queue !== []) {
            $current = array_shift($queue);
            $order[] = $current;

            foreach ($dependencies as $fieldKey => $refs) {
                if (! in_array($current, $refs, true) || ! array_key_exists($fieldKey, $remaining)) {
                    continue;
                }

                $remaining[$fieldKey]--;

                if ($remaining[$fieldKey] === 0) {
                    $queue[] = $fieldKey;
                    unset($remaining[$fieldKey]);
                }
            }
        }

        if (count($order) !== count($formulas)) {
            throw new InvalidArgumentException('Cannot evaluate formula map with dependency cycles.');
        }

        return $order;
    }

    /**
     * @param  array<string, list<string>>  $dependencies
     * @param  array<string, bool>  $visited
     * @param  array<string, bool>  $stack
     * @param  list<string>  $cycleKeys
     */
    private static function visit(
        string $node,
        array $dependencies,
        array &$visited,
        array &$stack,
        array &$cycleKeys,
    ): void {
        $visited[$node] = true;
        $stack[$node] = true;

        foreach ($dependencies[$node] ?? [] as $dependency) {
            if (! array_key_exists($dependency, $dependencies)) {
                continue;
            }

            if (isset($stack[$dependency])) {
                $cycleKeys[] = $node;
                $cycleKeys[] = $dependency;

                continue;
            }

            if (! isset($visited[$dependency])) {
                self::visit($dependency, $dependencies, $visited, $stack, $cycleKeys);
            }
        }

        unset($stack[$node]);
    }
}
