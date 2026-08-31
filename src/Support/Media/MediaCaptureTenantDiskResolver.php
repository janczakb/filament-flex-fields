<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media;

use Bjanczak\FilamentFlexFields\Support\FileUpload\ScopedDirectoryResolver;
use Illuminate\Database\Eloquent\Model;

final class MediaCaptureTenantDiskResolver
{
    /** @var (callable(array<string, mixed>): string|null)|null */
    private static $diskResolver = null;

    /** @var (callable(array<string, mixed>): string|null)|null */
    private static $directoryPrefixResolver = null;

    /**
     * @param  (callable(array<string, mixed> $context): string|null)|null  $resolver
     */
    public static function registerDiskResolver(?callable $resolver): void
    {
        self::$diskResolver = $resolver;
    }

    /**
     * @param  (callable(array<string, mixed> $context): string|null)|null  $resolver
     */
    public static function registerDirectoryPrefixResolver(?callable $resolver): void
    {
        self::$directoryPrefixResolver = $resolver;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function resolveDisk(?string $fallback = null, array $context = []): string
    {
        $fallback ??= (string) config('filament-flex-fields.media_capture.disk', config('filesystems.default'));

        if (self::$diskResolver !== null) {
            $resolved = (self::$diskResolver)($context);

            if (is_string($resolved) && filled($resolved)) {
                return $resolved;
            }
        }

        $tenantDisk = config('filament-flex-fields.media_capture.tenant.disk');

        if (is_string($tenantDisk) && filled($tenantDisk)) {
            return $tenantDisk;
        }

        return $fallback;
    }

    public static function resolveDirectory(
        string $prefix = 'uploads',
        ?Model $record = null,
        int|string|null $userId = null,
        array $context = [],
    ): string {
        $tenantPrefix = null;

        if (self::$directoryPrefixResolver !== null) {
            $tenantPrefix = (self::$directoryPrefixResolver)(array_merge($context, [
                'prefix' => $prefix,
                'record' => $record,
                'user_id' => $userId,
            ]));
        }

        if (! is_string($tenantPrefix) || $tenantPrefix === '') {
            $tenantPrefix = config('filament-flex-fields.media_capture.tenant.directory_prefix');
        }

        if (is_string($tenantPrefix) && filled($tenantPrefix)) {
            $prefix = trim($tenantPrefix.'/'.trim($prefix, '/'), '/');
        }

        return ScopedDirectoryResolver::resolve($prefix, $record, $userId);
    }

    public static function shouldApplyDirectoryPrefix(): bool
    {
        if (self::$directoryPrefixResolver !== null) {
            return true;
        }

        return filled(config('filament-flex-fields.media_capture.tenant.directory_prefix'));
    }

    public static function reset(): void
    {
        self::$diskResolver = null;
        self::$directoryPrefixResolver = null;
    }
}
