<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ColorSwatchField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class ColorSwatchPlayground
{
    /**
     * @return array<string, string>
     */
    protected function demoColors(): array
    {
        return [
            'charcoal' => '#18181b',
            'magenta' => '#d946ef',
            'blue' => '#3b82f6',
            'green' => '#22c55e',
            'yellow' => '#eab308',
            'white' => '#ffffff',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function demoColorLabels(): array
    {
        return [
            'charcoal' => 'Charcoal',
            'magenta' => 'Magenta',
            'blue' => 'Royal Blue',
            'green' => 'Emerald',
            'yellow' => 'Golden Yellow',
            'white' => 'White',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'color_swatch__sm' => 'green',
            'color_swatch__md' => 'blue',
            'color_swatch__lg' => 'magenta',
            'color_swatch__compact' => 'charcoal',
            'color_swatch__tooltips' => 'green',
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        $colors = $this->demoColors();
        $labels = $this->demoColorLabels();

        return [
            Section::make('Color swatch')
                ->description('HeroUI-style color pills with sm / md / lg sizes, hover tooltips and optional section header.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    Grid::make(3)
                        ->schema([
                            ColorSwatchField::make('color_swatch__sm')
                                ->label('Small')
                                ->size('sm')
                                ->sectionLabel('Predefined')
                                ->tooltips($labels)
                                ->colors($colors)
                                ->default('green'),
                            ColorSwatchField::make('color_swatch__md')
                                ->label('Medium')
                                ->size('md')
                                ->sectionLabel('Predefined')
                                ->tooltips($labels)
                                ->colors($colors)
                                ->default('blue'),
                            ColorSwatchField::make('color_swatch__lg')
                                ->label('Large')
                                ->size('lg')
                                ->sectionLabel('Predefined')
                                ->tooltips($labels)
                                ->colors($colors)
                                ->default('magenta'),
                        ]),
                    Grid::make(2)
                        ->schema([
                            ColorSwatchField::make('color_swatch__compact')
                                ->label('Compact (sm, no header)')
                                ->size('sm')
                                ->colors($colors)
                                ->default('charcoal')
                                ->helperText('Swatches only — tooltips disabled.'),
                            ColorSwatchField::make('color_swatch__tooltips')
                                ->label('Custom tooltip labels')
                                ->size('md')
                                ->sectionLabel('Predefined')
                                ->tooltips($labels)
                                ->colors($colors)
                                ->default('green')
                                ->helperText('Hover each pill to see a custom color name.'),
                        ]),
                ]),
        ];
    }
}
