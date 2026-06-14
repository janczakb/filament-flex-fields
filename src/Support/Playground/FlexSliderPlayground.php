<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexSlider;
use Filament\Forms\Components\Slider\Enums\PipsMode;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\RawJs;

class FlexSliderPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'flex_slider__step_demo' => 50,
            'flex_slider__sm' => 0,
            'flex_slider__md' => 50,
            'flex_slider__lg' => 100,
            'flex_slider__default' => 40,
            'flex_slider__secondary' => 60,
            'flex_slider__volume' => 65,
            'flex_slider__price_range' => [20, 80],
            'flex_slider__range_mid' => [25, 75],
            'flex_slider__tooltips' => 35,
            'flex_slider__tooltips_currency' => 42.5,
            'flex_slider__pips' => 50,
            'flex_slider__track_label' => 7,
            'flex_slider__hide_thumb' => 55,
            'flex_slider__value_start' => 30,
            'flex_slider__value_center' => 50,
            'flex_slider__value_end' => 70,
            'flex_slider__disabled' => 40,
        ];
    }

    public function section(): Section
    {
        return Section::make('Flex Slider')
            ->description('Pill rail + accent fill + capsule thumb + optional in-track step dots. Full Filament Slider API.')
            ->extraAttributes(['class' => 'fff-playground-section'])
            ->schema([
                Section::make('Pill thumb & step dots')
                    ->description('Capsule handle inside fill and optional in-track step markers via showStepDots().')
                    ->compact()
                    ->schema([
                        FlexSlider::make('flex_slider__step_demo')
                            ->hiddenLabel()
                            ->trackLabel('Price')
                            ->range(minValue: 0, maxValue: 100)
                            ->step(10)
                            ->showStepDots()
                            ->fillTrack()
                            ->tooltips(RawJs::make('`$${$value}`'))
                            ->default(50),
                    ]),
                Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        $this->single('flex_slider__sm', 'Slider Label')
                            ->size('sm')
                            ->default(0),
                        $this->single('flex_slider__md', 'Slider Label')
                            ->default(50),
                        $this->single('flex_slider__lg', 'Slider Label')
                            ->size('lg')
                            ->default(100),
                    ]),
                Section::make('Variants')
                    ->compact()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])
                            ->schema([
                                $this->single('flex_slider__default', 'Slider Label'),
                                $this->single('flex_slider__secondary', 'Slider Label')
                                    ->variant('secondary'),
                            ]),
                    ]),
                Section::make('Range')
                    ->compact()
                    ->schema([
                        FlexSlider::make('flex_slider__price_range')
                            ->hiddenLabel()
                            ->range(0, 100)
                            ->step(10)
                            ->fillTrack([false, true, false])
                            ->default([20, 80])
                            ->showValue(),
                        FlexSlider::make('flex_slider__range_mid')
                            ->hiddenLabel()
                            ->range(0, 100)
                            ->step(5)
                            ->fillTrack([false, true, false])
                            ->default([25, 75])
                            ->showValue(),
                    ]),
                Section::make('Range & step')
                    ->compact()
                    ->schema([
                        $this->single('flex_slider__volume', 'Volume')
                            ->range(0, 100)
                            ->step(5)
                            ->default(65),
                        $this->single('flex_slider__track_label', 'Guests')
                            ->range(1, 10)
                            ->step(1)
                            ->trackLabel('Guests')
                            ->default(7),
                    ]),
                Section::make('Tooltips & pips')
                    ->compact()
                    ->schema([
                        FlexSlider::make('flex_slider__tooltips')
                            ->hiddenLabel()
                            ->trackLabel('Opacity')
                            ->range(0, 100)
                            ->step(5)
                            ->suffix('%')
                            ->fillTrack()
                            ->tooltips()
                            ->default(35),
                        FlexSlider::make('flex_slider__tooltips_currency')
                            ->hiddenLabel()
                            ->trackLabel('Price')
                            ->range(minValue: 0, maxValue: 100)
                            ->step(0.5)
                            ->fillTrack()
                            ->tooltips(RawJs::make('`$${$value.toFixed(2)}`'))
                            ->default(42.5),
                        $this->single('flex_slider__pips', 'Rating')
                            ->range(0, 100)
                            ->step(10)
                            ->pips(PipsMode::Steps, 5),
                    ]),
                Section::make('Footer value position')
                    ->compact()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 3])
                            ->schema([
                                $this->single('flex_slider__value_start', 'Slider Label')
                                    ->valuePosition('start'),
                                $this->single('flex_slider__value_center', 'Slider Label')
                                    ->valuePosition('center'),
                                $this->single('flex_slider__value_end', 'Slider Label')
                                    ->valuePosition('end'),
                            ]),
                    ]),
                Section::make('Hide thumb until interaction')
                    ->compact()
                    ->schema([
                        $this->single('flex_slider__hide_thumb', 'Slider Label')
                            ->hideThumbUntilInteraction()
                            ->helperText('Grip handle appears on hover or while dragging'),
                    ]),
                Section::make('Disabled')
                    ->compact()
                    ->schema([
                        $this->single('flex_slider__disabled', 'Slider Label')
                            ->disabled(),
                    ]),
            ]);
    }

    protected function single(string $name, string $label): FlexSlider
    {
        return FlexSlider::make($name)
            ->hiddenLabel()
            ->trackLabel($label)
            ->range(0, 100)
            ->step(1)
            ->fillTrack()
            ->showValue();
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [$this->section()];
    }
}
