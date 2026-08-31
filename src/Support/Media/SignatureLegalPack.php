<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media;

use Bjanczak\FilamentFlexFields\Support\SignatureSvg;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Request;

final class SignatureLegalPack
{
    public static function requiresInk(mixed $state): bool
    {
        $svg = self::resolveSvg($state);

        if ($svg === null) {
            return false;
        }

        return ! SignatureSvg::isEmpty($svg);
    }

    /**
     * @return array{sealed_at: string}
     */
    public static function timestampSeal(?CarbonImmutable $at = null): array
    {
        $at ??= CarbonImmutable::now('UTC');

        return [
            'sealed_at' => $at->toIso8601String(),
        ];
    }

    /**
     * Enterprise legal audit metadata for e-sign workflows.
     *
     * @return array{
     *     sealed_at: string,
     *     signer_id: string|null,
     *     ip_address: string|null,
     *     user_agent: string|null,
     *     document_hash: string|null,
     *     signature_path_count: int,
     * }
     */
    public static function legalAuditSeal(mixed $signatureState, ?CarbonImmutable $at = null): array
    {
        $svg = self::resolveSvg($signatureState);

        return array_merge(self::timestampSeal($at), [
            'signer_id' => MediaCaptureOs::resolveLegalSignerId(),
            'ip_address' => self::resolveClientIp(),
            'user_agent' => self::resolveUserAgent(),
            'document_hash' => MediaCaptureOs::resolveDocumentHash(),
            'signature_path_count' => $svg !== null ? SignatureSvg::countPaths($svg) : 0,
        ]);
    }

    public static function resolveClientIp(): ?string
    {
        $ip = Request::ip();

        return is_string($ip) && filled($ip) ? $ip : null;
    }

    public static function resolveUserAgent(): ?string
    {
        $agent = Request::userAgent();

        if (! is_string($agent) || $agent === '') {
            return null;
        }

        return strlen($agent) > 512 ? substr($agent, 0, 512) : $agent;
    }

    private static function resolveSvg(mixed $state): ?string
    {
        if (is_string($state)) {
            $svg = trim($state);

            return $svg === '' ? null : $svg;
        }

        if (! is_array($state)) {
            return null;
        }

        $svg = $state['svg'] ?? $state['signature'] ?? null;

        if (! is_string($svg)) {
            return null;
        }

        $svg = trim($svg);

        return $svg === '' ? null : $svg;
    }
}
