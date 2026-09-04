<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Pages\FlexFieldsPlaygroundComponentPage;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;
use Filament\Schemas\Components\View;
use Throwable;

/**
 * Cross-links between layout playground hubs (composition, translatable, tabs, form layouts, item cards).
 */
final class PlaygroundRelatedHubs
{
    /**
     * @var list<string>
     */
    private const LAYOUT_HUBS = [
        'composition-recipes',
        'translatable-fields',
        'segment-tabs',
        'form-layouts',
        'item-card-group',
    ];

    public static function view(string $slug): View
    {
        return View::make('filament-flex-fields::partials.playground.related-hubs')
            ->viewData(self::viewData($slug));
    }

    /**
     * @return array{currentSlug: string, hubs: list<array{slug: string, label: string, url: string, docs_path: string|null}>}
     */
    public static function viewData(string $slug): array
    {
        $hubs = [];

        foreach (self::slugsFor($slug) as $relatedSlug) {
            $definition = FlexFieldsPlaygroundRegistry::find($relatedSlug);

            if ($definition === null) {
                continue;
            }

            $hubs[] = [
                'slug' => $relatedSlug,
                'label' => $definition['label'],
                'url' => self::urlForSlug($relatedSlug),
                'docs_path' => $definition['docs_path'] ?? FlexFieldsPlaygroundRegistry::docsPathFor($relatedSlug),
            ];
        }

        return [
            'currentSlug' => $slug,
            'hubs' => $hubs,
        ];
    }

    /**
     * @return list<string>
     */
    public static function slugsFor(string $slug): array
    {
        if (! in_array($slug, self::LAYOUT_HUBS, true)) {
            return [];
        }

        return array_values(array_filter(
            self::LAYOUT_HUBS,
            fn (string $candidate): bool => $candidate !== $slug,
        ));
    }

    public static function urlForSlug(string $slug): string
    {
        try {
            return FlexFieldsPlaygroundComponentPage::getUrl(configuration: $slug);
        } catch (Throwable) {
            return '/admin/flex-fields-playground/'.$slug;
        }
    }
}
