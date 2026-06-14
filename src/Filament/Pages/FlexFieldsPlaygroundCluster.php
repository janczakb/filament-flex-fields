<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Pages;

use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Enums\SubNavigationPosition;

class FlexFieldsPlaygroundCluster extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Flex Fields Playground';

    protected static ?string $title = 'Flex Fields Playground';

    protected static ?string $slug = 'flex-fields-playground';

    protected static ?int $navigationSort = 91;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    public static function shouldRegisterNavigation(): bool
    {
        return FlexFieldsPlaygroundRegistry::isEnabled();
    }

    public static function canAccess(): bool
    {
        return FlexFieldsPlaygroundRegistry::isEnabled()
            && auth()->check();
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return config(
            'filament-flex-fields.playground.navigation_group',
            FilamentFlexFieldsPlugin::make()->getNavigationGroup(),
        );
    }

    public static function getNavigationSort(): ?int
    {
        return (int) config(
            'filament-flex-fields.playground.navigation_sort',
            FilamentFlexFieldsPlugin::make()->getNavigationSort() ?? static::$navigationSort,
        );
    }

    public function mount(): void
    {
        $firstSlug = FlexFieldsPlaygroundRegistry::firstSlug();

        if (blank($firstSlug)) {
            abort(404);
        }

        redirect(FlexFieldsPlaygroundComponentPage::getUrl(configuration: $firstSlug));
    }

    /**
     * @return array<NavigationItem>
     */
    public function getSubNavigation(): array
    {
        $items = [];

        foreach (FlexFieldsPlaygroundRegistry::ordered() as $slug => $definition) {
            $items[] = NavigationItem::make($definition['label'])
                ->icon($definition['icon'])
                ->url(FlexFieldsPlaygroundComponentPage::getUrl(configuration: $slug))
                ->sort($definition['sort'])
                ->isActiveWhen(fn (): bool => Filament::getCurrentPageConfigurationKey() === $slug);
        }

        return $items;
    }
}
