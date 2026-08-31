<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureTenantDiskResolver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SignatureStorage
{
    public const string TOKEN_PREFIX = 'ffstage:';

    public static function store(string $svg, string $directory, ?string $diskName = null): string
    {
        $svg = trim($svg);

        if ($svg === '' || ! str_contains($svg, '<svg') || ! SignatureSvg::isValid($svg)) {
            throw new InvalidArgumentException('Invalid signature SVG.');
        }

        $svg = SignatureSvg::normalize($svg) ?? $svg;
        $disk = Storage::disk(self::resolveDiskName($diskName));
        $directory = trim($directory, '/');
        $filename = Str::uuid()->toString().'.svg';
        $relativePath = $directory.'/'.$filename;

        $disk->makeDirectory($directory);
        $disk->put($relativePath, $svg, [
            'visibility' => 'private',
            'ContentType' => 'image/svg+xml',
            'mimetype' => 'image/svg+xml',
        ]);

        return self::TOKEN_PREFIX.$relativePath;
    }

    public static function resolve(?string $state, ?string $diskName = null): ?string
    {
        if (! is_string($state) || trim($state) === '') {
            return null;
        }

        if (str_contains($state, '<svg')) {
            return $state;
        }

        $relativePath = self::relativePath($state);

        if ($relativePath === null) {
            return null;
        }

        $disk = Storage::disk(self::resolveDiskName($diskName));

        if (! $disk->exists($relativePath)) {
            return null;
        }

        $svg = $disk->get($relativePath);

        return is_string($svg) && str_contains($svg, '<svg') ? $svg : null;
    }

    public static function relativePath(string $state): ?string
    {
        if (str_starts_with($state, self::TOKEN_PREFIX)) {
            return substr($state, strlen(self::TOKEN_PREFIX));
        }

        if (str_ends_with(strtolower($state), '.svg') && ! str_contains($state, '<')) {
            return ltrim($state, '/');
        }

        return null;
    }

    private static function resolveDiskName(?string $diskName): string
    {
        if (is_string($diskName) && $diskName !== '') {
            return $diskName;
        }

        return MediaCaptureTenantDiskResolver::resolveDisk(null, [
            'adapter' => 'signature_storage',
        ]);
    }
}
