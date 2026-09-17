<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Pages;

use Bjanczak\FilamentFlexFields\Enums\PlaygroundCategory;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;
use Bjanczak\FilamentFlexFields\Support\Playground\PlaygroundArtCatalog;
use Bjanczak\FilamentFlexFields\Support\Playground\PlaygroundRelatedHubs;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class FlexFieldsPlaygroundIndexPage extends Page
{
    /**
     * @var class-string<FlexFieldsPlaygroundCluster>|null
     */
    protected static ?string $cluster = FlexFieldsPlaygroundCluster::class;

    protected static ?string $slug = 'index';

    protected static ?string $navigationLabel = 'All components';

    protected static ?string $title = 'All components';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = -100;

    protected string $view = 'filament-flex-fields::pages.flex-fields-playground-index';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return FlexFieldsPlaygroundRegistry::isEnabled()
            && auth()->check();
    }

    public function getTitle(): string|Htmlable
    {
        return 'All components';
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    /**
     * @return list<\Filament\Navigation\NavigationItem>
     */
    public function getSubNavigation(): array
    {
        return app(FlexFieldsPlaygroundCluster::class)->getSubNavigation();
    }

    /**
     * @return list<array{
     *     category: PlaygroundCategory,
     *     label: string,
     *     hubs: list<array{slug: string, label: string, icon: string, url: string, image: string|null}>
     * }>
     */
    public function catalogSections(): array
    {
        $sections = [];

        foreach (FlexFieldsPlaygroundRegistry::groupedByCategory() as $categoryValue => $hubs) {
            if ($hubs === []) {
                continue;
            }

            $category = PlaygroundCategory::from((string) $categoryValue);
            $cards = [];

            foreach ($hubs as $slug => $definition) {
                $cards[] = [
                    'slug' => $slug,
                    'label' => $definition['label'],
                    'icon' => $definition['icon'],
                    'url' => PlaygroundRelatedHubs::urlForSlug($slug) ?? '/admin/flex-fields-playground/'.$slug,
                    'image' => PlaygroundArtCatalog::urlFor($slug),
                ];
            }

            $sections[] = [
                'category' => $category,
                'label' => $category->label(),
                'hubs' => $cards,
            ];
        }

        return $sections;
    }
}
