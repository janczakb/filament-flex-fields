<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TrackSlider;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class TrackSliderPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'track_slider__sm' => 0.5,
            'track_slider__md' => 0.47,
            'track_slider__lg' => 0.5,
            'track_slider__controlled' => 0.47,
            'track_slider__spacing' => 0.22,
            'track_slider__font_size' => 0.91,
            'track_slider__general_radius' => 0.76,
            'track_slider__forms_radius' => 0.48,
            'track_slider__volume' => 75,
            'track_slider__step_5' => 10,
            'track_slider__default' => 0.5,
            'track_slider__secondary' => 0.5,
            'track_slider__secondary_spacing' => 0.86,
            'track_slider__secondary_font_size' => 0.91,
            'track_slider__disabled_spacing' => 0.5,
            'track_slider__disabled_font' => 0.3,
            'track_slider__guests' => 1,
        ];
    }

    public function section(): Section
    {
        return Section::make('Track Slider')
            ->description('Filament label above the track; trackLabel() caption inside the bar on the left.')
            ->extraAttributes(['class' => 'fff-playground-section'])
            ->schema([
                Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        $this->decimalField('track_slider__sm', 'Spacing')
                            ->size('sm'),
                        $this->decimalField('track_slider__md', 'Spacing'),
                        $this->decimalField('track_slider__lg', 'Spacing')
                            ->size('lg'),
                    ]),
                Section::make('Controlled')
                    ->compact()
                    ->schema([
                        $this->decimalField('track_slider__controlled', 'Spacing')
                            ->helperText('Value: 0.47 — drag or click anywhere on the bar'),
                    ]),
                Section::make('Settings group')
                    ->compact()
                    ->schema([
                        $this->decimalField('track_slider__spacing', 'Spacing'),
                        $this->decimalField('track_slider__font_size', 'Font Size'),
                    ]),
                Section::make('Corners')
                    ->compact()
                    ->schema([
                        $this->decimalField('track_slider__general_radius', 'General Radius'),
                        $this->decimalField('track_slider__forms_radius', 'Forms Radius'),
                    ]),
                Section::make('Integer step')
                    ->compact()
                    ->schema([
                        $this->integerField('track_slider__volume', 'Volume', 0, 100, 1),
                        $this->integerField('track_slider__step_5', 'Quantity', 0, 50, 5),
                    ]),
                Section::make('Variants')
                    ->compact()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])
                            ->schema([
                                $this->decimalField('track_slider__default', 'Spacing')
                                    ->variant('default'),
                                $this->decimalField('track_slider__secondary', 'Spacing')
                                    ->variant('secondary'),
                            ]),
                    ]),
                Section::make('Secondary group')
                    ->compact()
                    ->schema([
                        $this->decimalField('track_slider__secondary_spacing', 'Spacing')
                            ->variant('secondary'),
                        $this->decimalField('track_slider__secondary_font_size', 'Font Size')
                            ->variant('secondary'),
                    ]),
                Section::make('With label')
                    ->compact()
                    ->schema([
                        $this->integerField('track_slider__guests', 'Guests', 1, 10, 1)
                            ->helperText('Maximum 10 guests per reservation'),
                    ]),
                Section::make('Disabled')
                    ->compact()
                    ->schema([
                        $this->decimalField('track_slider__disabled_spacing', 'Spacing')
                            ->disabled(),
                        $this->decimalField('track_slider__disabled_font', 'Font Size')
                            ->disabled(),
                    ]),
            ]);
    }

    protected function trackSlider(string $name, string $label): TrackSlider
    {
        return TrackSlider::make($name)
            ->label($label)
            ->trackLabel($label);
    }

    protected function decimalField(string $name, string $label): TrackSlider
    {
        return $this->trackSlider($name, $label)
            ->min(0)
            ->max(1)
            ->step(0.01)
            ->integer(false)
            ->decimalPlaces(2);
    }

    protected function integerField(string $name, string $label, int $min, int $max, int $step): TrackSlider
    {
        return $this->trackSlider($name, $label)
            ->min($min)
            ->max($max)
            ->step($step);
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [$this->section()];
    }
}
