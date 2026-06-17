<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Http;

/**
 * SSRF-safe HTTP(S) URL validation shared by remote fetchers.
 */
class SafeRemoteUrlValidator
{
    public function isAllowedHttpUrl(string $url): bool
    {
        return $this->isValidHttpUrl($url) && $this->isSafeTarget($url);
    }

    public function isValidHttpUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }

    public function isSafeTarget(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['host'])) {
            return false;
        }

        $host = strtolower($parts['host']);

        if ($this->isBlockedHostname($host)) {
            return false;
        }

        if (str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return false;
        }

        return $this->resolvesToPublicIps($host);
    }

    private function resolvesToPublicIps(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->isPublicIp($host);
        }

        $ips = @gethostbynamel($host);

        if (empty($ips)) {
            return false;
        }

        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    private function isBlockedHostname(string $host): bool
    {
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return true;
        }

        return in_array($host, [
            'metadata.google.internal',
            'metadata.goog',
        ], true);
    }

    private function isPublicIp(string $ip): bool
    {
        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
