<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media;

use Illuminate\Support\Facades\Storage;

final class LaravelStorageSignedUrlResolver
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __invoke(string $disk, string $path, array $context = []): ?string
    {
        $storage = Storage::disk($disk);

        if (! method_exists($storage, 'temporaryUrl')) {
            return null;
        }

        try {
            $minutes = (int) ($context['minutes'] ?? config('filament-flex-fields.media_capture.signed_url_minutes', 15));

            return $storage->temporaryUrl($path, now()->addMinutes(max(1, $minutes)));
        } catch (\Throwable) {
            return null;
        }
    }
}
