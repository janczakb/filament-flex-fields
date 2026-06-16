<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\SocialLinks;

use Bjanczak\FilamentFlexFields\Data\SocialPlatform;

final class SocialLinksNormalizer
{
    /**
     * @return list<array{platform: string, url: string}>
     */
    public static function normalize(mixed $state): array
    {
        if (! is_array($state)) {
            return [];
        }

        $links = [];

        if (self::isAssociativePlatformMap($state)) {
            foreach ($state as $platform => $url) {
                if (! is_string($platform)) {
                    continue;
                }

                $platform = trim($platform);

                if ($platform === '') {
                    continue;
                }

                $links[] = [
                    'platform' => $platform,
                    'url' => is_string($url) ? trim($url) : '',
                ];
            }

            return $links;
        }

        foreach ($state as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $platform = isset($entry['platform']) ? trim((string) $entry['platform']) : '';
            $url = isset($entry['url']) ? trim((string) $entry['url']) : '';

            if ($platform === '') {
                continue;
            }

            $links[] = [
                'platform' => $platform,
                'url' => $url,
            ];
        }

        return self::deduplicatePlatforms($links);
    }

    /**
     * @param  list<array{platform: string, url: string}>  $links
     * @return list<array{platform: string, url: string}>
     */
    public static function deduplicatePlatforms(array $links): array
    {
        $seen = [];
        $normalized = [];

        foreach ($links as $link) {
            $platform = $link['platform'];

            if (isset($seen[$platform])) {
                continue;
            }

            $seen[$platform] = true;
            $normalized[] = $link;
        }

        return $normalized;
    }

    /**
     * @param  list<array{platform: string, url: string}>  $links
     * @return list<array{platform: string, url: string}>|null
     */
    public static function dehydrate(array $links): ?array
    {
        $persisted = array_values(array_filter(
            $links,
            fn (array $link): bool => trim($link['url'] ?? '') !== '',
        ));

        if ($persisted === []) {
            return null;
        }

        return $persisted;
    }

    public static function isEmpty(mixed $state): bool
    {
        return self::dehydrate(self::normalize($state)) === null;
    }

    /**
     * @param  array<mixed, mixed>  $state
     */
    private static function isAssociativePlatformMap(array $state): bool
    {
        if ($state === []) {
            return false;
        }

        foreach (array_keys($state) as $key) {
            if (! is_string($key) || SocialPlatform::tryFrom($key) === null) {
                return false;
            }
        }

        return true;
    }
}
