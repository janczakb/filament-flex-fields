<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\RichEditor;

use Illuminate\Contracts\Filesystem\Filesystem;

class RichEditorAttachmentManifestRepository
{
    /**
     * @var array<string, RichEditorAttachmentManifest|null>
     */
    private static array $cache = [];

    public function read(Filesystem $disk, string $masterPath): ?RichEditorAttachmentManifest
    {
        $cacheKey = spl_object_id($disk).':'.$masterPath;

        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }

        $manifestPath = RichEditorAttachmentPaths::manifestPath($masterPath);

        if (! $disk->exists($manifestPath)) {
            return self::$cache[$cacheKey] = null;
        }

        $payload = json_decode((string) $disk->get($manifestPath), true);

        if (! is_array($payload)) {
            return self::$cache[$cacheKey] = null;
        }

        return self::$cache[$cacheKey] = RichEditorAttachmentManifest::fromArray($payload);
    }
}
