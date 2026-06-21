<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\RichEditor;

class RichEditorSpatieMediaRepository
{
    /**
     * @var array<string, object|null>
     */
    private static array $cache = [];

    public function findByUuid(string $uuid): ?object
    {
        if (array_key_exists($uuid, self::$cache)) {
            return self::$cache[$uuid];
        }

        $mediaClass = 'Spatie\\MediaLibrary\\MediaCollections\\Models\\Media';

        if (! class_exists($mediaClass)) {
            return self::$cache[$uuid] = null;
        }

        return self::$cache[$uuid] = $mediaClass::query()->where('uuid', $uuid)->first();
    }
}
