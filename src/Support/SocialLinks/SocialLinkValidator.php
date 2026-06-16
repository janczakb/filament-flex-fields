<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\SocialLinks;

use Illuminate\Support\Str;

final class SocialLinkValidator
{
    /**
     * @param  list<string>  $allowedPlatforms
     * @param  array<string, SocialPlatformDefinition>|null  $definitions
     */
    public static function validateLink(
        string $platform,
        string $url,
        array $allowedPlatforms = [],
        ?array $definitions = null,
    ): ?string {
        $platform = trim($platform);
        $url = trim($url);

        if ($platform === '' || $url === '') {
            return __('filament-flex-fields::default.social_links.validation.required');
        }

        $definition = $definitions[$platform] ?? null;

        if ($definition === null) {
            return __('filament-flex-fields::default.social_links.validation.unknown_platform');
        }

        if ($allowedPlatforms !== [] && ! in_array($platform, $allowedPlatforms, true)) {
            return __('filament-flex-fields::default.social_links.validation.platform_not_allowed');
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return __('filament-flex-fields::default.social_links.validation.invalid_url');
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! in_array($scheme, ['http', 'https'], true)) {
            return __('filament-flex-fields::default.social_links.validation.invalid_url');
        }

        if ($definition->hosts === []) {
            return null;
        }

        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));
        $host = Str::replaceStart('www.', '', $host);

        foreach ($definition->hosts as $pattern) {
            if ($host === $pattern || Str::endsWith($host, '.'.$pattern)) {
                return null;
            }
        }

        return __('filament-flex-fields::default.social_links.validation.platform_mismatch', [
            'platform' => $definition->label,
        ]);
    }

    /**
     * @param  list<array{platform: string, url: string}>  $links
     * @param  list<string>  $allowedPlatforms
     * @param  array<string, SocialPlatformDefinition>|null  $definitions
     * @return array<int, string>
     */
    public static function validateCollection(
        array $links,
        array $allowedPlatforms = [],
        ?array $definitions = null,
    ): array {
        $errors = [];

        foreach ($links as $index => $link) {
            $message = self::validateLink(
                $link['platform'] ?? '',
                $link['url'] ?? '',
                $allowedPlatforms,
                $definitions,
            );

            if ($message !== null) {
                $errors[$index] = $message;
            }
        }

        return $errors;
    }
}
