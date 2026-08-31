<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Support\Intelligence;

use InvalidArgumentException;

/**
 * Cross-form field sync watchers and conflict resolution helpers.
 */
final class CrossFormSync
{
    public const STRATEGY_LAST_WRITE = 'last_write';

    public const STRATEGY_MERGE = 'merge';

    /**
     * @var list<array{source: string, target: string, strategy: string}>
     */
    private array $watchers = [];

    public function watch(string $sourcePath, string $targetPath, string $strategy = self::STRATEGY_LAST_WRITE): self
    {
        if (! in_array($strategy, [self::STRATEGY_LAST_WRITE, self::STRATEGY_MERGE], true)) {
            throw new InvalidArgumentException("Unsupported sync strategy [{$strategy}].");
        }

        $this->watchers[] = [
            'source' => $sourcePath,
            'target' => $targetPath,
            'strategy' => $strategy,
        ];

        return $this;
    }

    /**
     * @return list<array{source: string, target: string, strategy: string}>
     */
    public function watchers(): array
    {
        return $this->watchers;
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $target
     * @return array<string, mixed>
     */
    public static function resolveConflictMerge(array $source, array $target): array
    {
        return array_replace_recursive($target, $source);
    }

    public static function resolveConflictLastWrite(mixed $source, mixed $target, int $sourceUpdatedAt, int $targetUpdatedAt): mixed
    {
        return $sourceUpdatedAt >= $targetUpdatedAt ? $source : $target;
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $target
     * @return array<string, mixed>|mixed
     */
    public static function resolveConflict(string $strategy, mixed $source, mixed $target, int $sourceUpdatedAt = 0, int $targetUpdatedAt = 0): mixed
    {
        return match ($strategy) {
            self::STRATEGY_MERGE => self::resolveConflictMerge(
                is_array($source) ? $source : [],
                is_array($target) ? $target : [],
            ),
            self::STRATEGY_LAST_WRITE => self::resolveConflictLastWrite($source, $target, $sourceUpdatedAt, $targetUpdatedAt),
            default => throw new InvalidArgumentException("Unsupported sync strategy [{$strategy}]."),
        };
    }
}
