<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Pages;

use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Enums\SubNavigationPosition;
use Illuminate\Support\Facades\Auth;

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
            && Auth::check();
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
        redirect(FlexFieldsPlaygroundIndexPage::getUrl());
    }

    /**
     * @return list<NavigationItem>
     */
    public function getSubNavigation(): array
    {
        $items = [
            NavigationItem::make('All components')
                ->icon('heroicon-o-squares-2x2')
                ->url(FlexFieldsPlaygroundIndexPage::getUrl())
                ->sort(-100)
                ->isActiveWhen(fn (): bool => request()->routeIs(
                    FlexFieldsPlaygroundIndexPage::getRouteName(),
                ) || str_ends_with(rtrim(request()->path(), '/'), '/flex-fields-playground/index')),
        ];

        foreach (FlexFieldsPlaygroundRegistry::ordered() as $slug => $definition) {
            $item = NavigationItem::make($definition['label'])
                ->icon($definition['icon'])
                ->group($definition['category']->label())
                ->url(FlexFieldsPlaygroundComponentPage::getUrl(configuration: $slug))
                ->sort($definition['sort'])
                ->isActiveWhen(fn (): bool => Filament::getCurrentPageConfigurationKey() === $slug);

            if (isset($definition['badge'])) {
                $item->badge($definition['badge'], $definition['badgeColor'] ?? null);
            }

            $items[] = $item;
        }

        return $items;
    }
}
