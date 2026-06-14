<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FileUpload;

use Illuminate\Database\Eloquent\Model;

class ScopedDirectoryResolver
{
    public static function resolve(string $prefix = 'uploads', ?Model $record = null, int|string|null $userId = null): string
    {
        $prefix = trim($prefix, '/');

        if ($record instanceof Model && filled($record->getKey())) {
            $basename = class_basename($record);

            return trim("{$prefix}/{$basename}/{$record->getKey()}", '/');
        }

        $userSegment = filled($userId) ? (string) $userId : 'guest';

        return trim("{$prefix}/drafts/{$userSegment}", '/');
    }
}
