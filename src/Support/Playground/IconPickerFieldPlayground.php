<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\IconPickerField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class IconPickerFieldPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'icon_picker__heroicon' => 'heroicon-o-star',
            'icon_picker__gravity' => 'gravityui-star',
            'icon_picker__multi_set' => 'heroicon-o-star',
            'icon_picker__grid' => 'heroicon-o-bolt',
            'icon_picker__whitelist' => 'heroicon-o-heart',
            'icon_picker__sm' => 'heroicon-o-star',
            'icon_picker__md' => 'heroicon-o-bolt',
            'icon_picker__lg' => 'heroicon-o-heart',
            'icon_picker__flat' => 'heroicon-o-star',
            'icon_picker__soft' => 'heroicon-o-bolt',
            'icon_picker__faded' => 'heroicon-o-heart',
            'icon_picker__underlined' => 'heroicon-o-star',
            'icon_picker__secondary' => 'heroicon-o-bolt',
            'icon_picker__not_clearable' => 'heroicon-o-star',
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Icon picker')
                ->description('Searchable blade-icons picker with lazy SVG rendering, set filters, and paginated server search.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    IconPickerField::make('icon_picker__heroicon')
                        ->label('Heroicons only')
                        ->sets(['heroicons'])
                        ->helperText('Limits the catalog to the heroicons set.')
                        ->columnSpanFull(),
                    IconPickerField::make('icon_picker__gravity')
                        ->label('Gravity icons only')
                        ->sets(['gravity-icons'])
                        ->helperText('Limits the catalog to the gravity-icons set.')
                        ->columnSpanFull(),
                    IconPickerField::make('icon_picker__multi_set')
                        ->label('Multiple sets (set filter pills)')
                        ->sets(['heroicons', 'gravity-icons'])
                        ->helperText('Open the dropdown — All sets, Heroicon, and Gravityui pills appear above the search field.')
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            IconPickerField::make('icon_picker__grid')
                                ->label('Icon grid')
                                ->searchResultsLayout('icons')
                                ->gridColumns(8)
                                ->sets(['heroicons'])
                                ->helperText('Dropdown with icon-only grid layout.'),
                            IconPickerField::make('icon_picker__whitelist')
                                ->label('Whitelisted icons')
                                ->sets(['heroicons'])
                                ->icons([
                                    'heroicon-o-heart',
                                    'heroicon-o-star',
                                    'heroicon-o-bolt',
                                    'heroicon-o-fire',
                                ])
                                ->excludeIcons(['heroicon-o-fire'])
                                ->gridColumns(4),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            IconPickerField::make('icon_picker__sm')
                                ->label('Small')
                                ->sets(['heroicons'])
                                ->size('sm'),
                            IconPickerField::make('icon_picker__md')
                                ->label('Medium')
                                ->sets(['heroicons']),
                            IconPickerField::make('icon_picker__lg')
                                ->label('Large')
                                ->sets(['heroicons'])
                                ->size('lg'),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            IconPickerField::make('icon_picker__flat')
                                ->label('Flat')
                                ->sets(['heroicons'])
                                ->variant('flat'),
                            IconPickerField::make('icon_picker__soft')
                                ->label('Soft')
                                ->sets(['heroicons'])
                                ->variant('soft'),
                            IconPickerField::make('icon_picker__faded')
                                ->label('Faded')
                                ->sets(['heroicons'])
                                ->variant('faded'),
                            IconPickerField::make('icon_picker__underlined')
                                ->label('Underlined')
                                ->sets(['heroicons'])
                                ->variant('underlined'),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            IconPickerField::make('icon_picker__secondary')
                                ->label('Secondary')
                                ->sets(['heroicons'])
                                ->variant('secondary'),
                            IconPickerField::make('icon_picker__not_clearable')
                                ->label('Not clearable')
                                ->sets(['heroicons'])
                                ->clearable(false),
                        ]),
                ]),
        ];
    }
}
