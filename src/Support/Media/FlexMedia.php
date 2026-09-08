<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media;

use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaContext;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaIngress;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaPayload;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Refs\MediaRef;
use Closure;

/**
 * Public product surface for Media Ingress. Prefer this over {@see MediaCaptureOs} for new code.
 *
 * {@see MediaCaptureOs} remains as a deprecated facade for host-registered hooks (virus scan, signed URLs, etc.).
 */
final class FlexMedia
{
    public static function ingress(): MediaIngress
    {
        return app(MediaIngress::class);
    }

    /**
     * @param  (Closure(MediaRef, MediaContext): MediaRef|null)|null  $afterPersist
     */
    public static function persist(
        MediaPayload $payload,
        MediaContext $context,
        ?Closure $afterPersist = null,
    ): ?MediaRef {
        return self::ingress()->persist($payload, $context, $afterPersist);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function signedUrl(string $disk, string $path, array $context = []): ?string
    {
        return self::ingress()->resolveSignedUrl($disk, $path, $context);
    }

    /**
     * @deprecated Use {@see MediaIngress} / {@see FlexMedia::persist()} capsules. Kept as alias into the container.
     *
     * @param  (callable(string $path): bool|null)|null  $callback
     */
    public static function registerVirusScanCallback(?callable $callback): void
    {
        MediaCaptureOs::registerVirusScanCallback($callback);
    }

    /**
     * @deprecated Use {@see FlexMedia::signedUrl()} / MediaIngress signed URL capsule.
     *
     * @param  (callable(string $disk, string $path, array<string, mixed> $context): string|null)|null  $resolver
     */
    public static function registerSignedUploadUrlResolver(?callable $resolver): void
    {
        MediaCaptureOs::registerSignedUploadUrlResolver($resolver);
    }
}
