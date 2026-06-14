<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexColorPickerField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class FlexColorPickerPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'flex_color_picker__advanced' => '#6366F1',
            'flex_color_picker__grid' => '#FCA5A5',
            'flex_color_picker__secondary' => '#84D0D0',
            'flex_color_picker__alpha' => 'rgba(244, 63, 94, 0.72)',
            'flex_color_picker__hsl' => 'hsl(262, 83%, 58%)',
            'flex_color_picker__readonly' => '#F59E0B',
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Flex color picker')
                ->description('Advanced HSV panel with eyedropper, or preset grid layout. Stores hex, rgb, hsl, or rgba depending on format().')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    FlexColorPickerField::make('flex_color_picker__advanced')
                        ->label('Advanced layout')
                        ->helperText('Saturation/value square, hue and optional alpha sliders, format switcher.')
                        ->alpha()
                        ->columnSpanFull(),
                    FlexColorPickerField::make('flex_color_picker__grid')
                        ->label('Grid layout')
                        ->layout(FlexColorPickerField::LAYOUT_GRID)
                        ->alpha()
                        ->columnSpanFull(),
                    FlexColorPickerField::make('flex_color_picker__secondary')
                        ->label('Secondary variant')
                        ->variant('secondary')
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            FlexColorPickerField::make('flex_color_picker__alpha')
                                ->label('RGBA output')
                                ->rgba()
                                ->alpha()
                                ->eyedropper(false),
                            FlexColorPickerField::make('flex_color_picker__hsl')
                                ->label('HSL output')
                                ->hsl()
                                ->size('sm'),
                            FlexColorPickerField::make('flex_color_picker__readonly')
                                ->label('Read only')
                                ->rgb()
                                ->readOnly(),
                        ]),
                ]),
        ];
    }
}
