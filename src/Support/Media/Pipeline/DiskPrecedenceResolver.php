<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media\Pipeline;

use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureTenantDiskResolver;

/**
 * Tenant / disk precedence (documented + enforced):
 * 1. Explicit field disk
 * 2. Spatie collection / media-disk argument
 * 3. MediaCaptureTenantDiskResolver (auto_disk / tenant.disk)
 * 4. Package media_capture.disk default
 *
 * Path-prefix tenancy is separate from Spatie collection tenancy.
 * For Spatie, tenant auto_disk never silently overrides an explicit or collection disk.
 */
final class DiskPrecedenceResolver
{
    /**
     * @param  array<string, mixed>  $tenantContext
     */
    public static function resolve(
        ?string $explicitDisk = null,
        ?string $collectionDisk = null,
        array $tenantContext = [],
        bool $allowTenantOverride = true,
    ): string {
        if (is_string($explicitDisk) && filled($explicitDisk)) {
            return $explicitDisk;
        }

        if (is_string($collectionDisk) && filled($collectionDisk)) {
            return $collectionDisk;
        }

        if ($allowTenantOverride) {
            return MediaCaptureTenantDiskResolver::resolveDisk(null, $tenantContext);
        }

        return (string) config('filament-flex-fields.media_capture.disk', config('filesystems.default'));
    }

    /**
     * @param  array<string, mixed>  $tenantContext
     */
    public static function resolveForSpatie(
        ?string $explicitDisk = null,
        ?string $collectionDisk = null,
        array $tenantContext = [],
    ): string {
        // Never let tenant auto_disk clobber Spatie collection / explicit disks.
        if ((is_string($explicitDisk) && filled($explicitDisk))
            || (is_string($collectionDisk) && filled($collectionDisk))) {
            return self::resolve($explicitDisk, $collectionDisk, $tenantContext, allowTenantOverride: false);
        }

        $autoDisk = (bool) config('filament-flex-fields.media_capture.tenant.auto_disk', false);

        return self::resolve(
            null,
            null,
            $tenantContext,
            allowTenantOverride: $autoDisk,
        );
    }
}
