<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Security;

use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;

class SafeMediaUrl
{
    /**
     * Dangerous URL schemes that must never be used as media sources.
     *
     * @var list<string>
     */
    protected const BLOCKED_SCHEMES = [
        'javascript:',
        'data:',
        'vbscript:',
        'file:',
    ];

    public static function isAllowed(?string $url): bool
    {
        return self::sanitize($url) !== null;
    }

    public static function sanitize(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $trimmed = trim($url);

        if ($trimmed === '') {
            return null;
        }

        $lower = strtolower($trimmed);

        foreach (self::BLOCKED_SCHEMES as $scheme) {
            if (str_starts_with($lower, $scheme)) {
                return null;
            }
        }

        if (str_starts_with($trimmed, '/')) {
            return $trimmed;
        }

        if (str_starts_with($lower, 'https://')) {
            return $trimmed;
        }

        if (str_starts_with($lower, 'http://') && self::allowsHttp()) {
            return $trimmed;
        }

        if (self::isAllowedEmbedUrl($trimmed)) {
            return $trimmed;
        }

        return null;
    }

    protected static function allowsHttp(): bool
    {
        return FlexFieldsConfig::allowHttpMedia();
    }

    protected static function isAllowedEmbedUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower($host);

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        $allowedHosts = [
            'youtube.com',
            'youtu.be',
            'm.youtube.com',
            'www.youtube.com',
            'www.youtu.be',
            'vimeo.com',
            'player.vimeo.com',
            'www.vimeo.com',
        ];

        foreach ($allowedHosts as $allowedHost) {
            if ($host === $allowedHost || str_ends_with($host, '.'.$allowedHost)) {
                return true;
            }
        }

        return false;
    }
}
