<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\UrlMeta;

class RedirectUrlResolver
{
    public function resolve(string $currentUrl, string $location): string
    {
        if (filter_var($location, FILTER_VALIDATE_URL)) {
            return $location;
        }

        $parts = parse_url($currentUrl) ?: [];
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';

        if (str_starts_with($location, '//')) {
            return $scheme.':'.$location;
        }

        if (str_starts_with($location, '/')) {
            return $scheme.'://'.$host.$location;
        }

        $path = $parts['path'] ?? '/';
        $directory = rtrim(str_replace('\\', '/', dirname($path)), '/');

        return $scheme.'://'.$host.($directory !== '' ? $directory.'/' : '/').ltrim($location, '/');
    }
}
