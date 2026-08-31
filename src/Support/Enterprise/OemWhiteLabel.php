<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Enterprise;

final class OemWhiteLabel
{
    /** @var array<string, string> */
    private static array $tokenRenameMap = [];

    private static ?string $licenseBannerSlotHtml = null;

    /**
     * @param  array<string, string>  $map
     */
    public static function tokenRenameMap(?array $map = null): array
    {
        if ($map !== null) {
            self::$tokenRenameMap = $map;
        }

        return self::$tokenRenameMap;
    }

    public static function licenseBannerSlotHtml(?string $html = null): ?string
    {
        if (func_num_args() === 1) {
            self::$licenseBannerSlotHtml = $html;
        }

        return self::$licenseBannerSlotHtml;
    }

    /**
     * @return list<string>
     */
    public static function brandWipeGuideSteps(): array
    {
        return [
            'Replace CSS token prefixes via tokenRenameMap (--fff-* → your brand).',
            'Remove or override playground navigation entries that mention Flex Fields.',
            'Set licenseBannerSlotHtml to your OEM compliance notice or leave empty.',
            'Publish vendor lang files under your namespace and drop filament-flex-fields branding strings.',
            'Run bundle export and grep dist/ for "flex-fields" asset paths before customer handoff.',
        ];
    }

    public static function clear(): void
    {
        self::$tokenRenameMap = [];
        self::$licenseBannerSlotHtml = null;
    }
}
