<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media;

interface MediaStorageContract
{
    /**
     * @return array{
     *     uploads: array{enabled: bool, days: int|null},
     *     voice_notes: array{enabled: bool, days: int|null},
     *     signatures: array{enabled: bool, days: int|null},
     *     temp_captures: array{enabled: bool, days: int|null},
     * }
     */
    public static function retentionPolicies(): array;

    public static function passesVirusScan(string $path): bool;
}
