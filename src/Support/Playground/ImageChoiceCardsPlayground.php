<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ImageChoiceCards;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class ImageChoiceCardsPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'image_choice_cards__square' => 'athletic',
            'image_choice_cards__overlay' => 'athletic',
            'image_choice_cards__destination' => 'santorini',
            'image_choice_cards__multi' => ['yoga', 'strength'],
            'image_choice_cards__size_sm' => 'warm',
            'image_choice_cards__size_md' => 'cool',
            'image_choice_cards__size_lg' => 'neutral',
            'image_choice_cards__tier' => 'a',
            'image_choice_cards__dependent' => 'catamaran',
            'image_choice_cards__validation' => null,
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [$this->section()];
    }

    public function section(): Section
    {
        return Section::make('Image Choice Cards')
            ->description('Image cards with footer label + indicator. Radio exclusive or checkbox multi with min/max and reactive disabled options.')
            ->extraAttributes(['class' => 'fff-playground-section'])
            ->schema([
                Section::make('Square 1:1')
                    ->compact()
                    ->schema([
                        ImageChoiceCards::make('image_choice_cards__square')
                            ->label('Body type')
                            ->helperText('imageAspectRatio(1/1) — square media with footer bar highlight.')
                            ->options($this->squareBodyOptions())
                            ->multiple(false)
                            ->gridColumns(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 4])
                            ->size('md')
                            ->rounding('lg')
                            ->imageAspectRatio('1/1')
                            ->imageFit('cover')
                            ->default('athletic'),
                    ]),
                Section::make('Overlay layout')
                    ->compact()
                    ->schema([
                        ImageChoiceCards::make('image_choice_cards__overlay')
                            ->label('Body type')
                            ->helperText('variant(\'overlay\') — image fills the card; footer bar overlaps the bottom.')
                            ->options($this->squareBodyOptions())
                            ->variant('overlay')
                            ->multiple(false)
                            ->gridColumns(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 4])
                            ->size('md')
                            ->rounding('lg')
                            ->imageAspectRatio('3/4')
                            ->imageFit('cover')
                            ->default('athletic'),
                    ]),
                Section::make('Radio exclusive')
                    ->compact()
                    ->schema([
                        ImageChoiceCards::make('image_choice_cards__destination')
                            ->label('Charter destination')
                            ->helperText('Selecting another card replaces the previous choice (radio).')
                            ->options($this->destinationOptions())
                            ->multiple(false)
                            ->gridColumns(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 4])
                            ->size('md')
                            ->rounding('lg')
                            ->imageAspectRatio('3/4')
                            ->default('santorini'),
                    ]),
                Section::make('Checkbox · min / max')
                    ->compact()
                    ->schema([
                        ImageChoiceCards::make('image_choice_cards__multi')
                            ->label('Training focus')
                            ->helperText('Select 1–2 focuses. Further selections are blocked at max.')
                            ->options($this->focusOptions())
                            ->multiple()
                            ->minSelections(1)
                            ->maxSelections(2)
                            ->gridColumns(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 4])
                            ->size('md')
                            ->rounding('lg'),
                    ]),
                Section::make('Size · rounding · aspect')
                    ->compact()
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2, '2xl' => 3])
                            ->schema([
                                ImageChoiceCards::make('image_choice_cards__size_sm')
                                    ->label('Size sm · rounding sm · 1/1')
                                    ->options($this->galleryPresetOptions())
                                    ->size('sm')
                                    ->rounding('sm')
                                    ->imageAspectRatio('1/1')
                                    ->gridColumns(2),
                                ImageChoiceCards::make('image_choice_cards__size_md')
                                    ->label('Size md · rounding md · 4/5')
                                    ->options($this->galleryPresetOptions())
                                    ->size('md')
                                    ->rounding('md')
                                    ->imageAspectRatio('4/5')
                                    ->imageFit('cover')
                                    ->gridColumns(2),
                                ImageChoiceCards::make('image_choice_cards__size_lg')
                                    ->label('Size lg · rounding lg · 3/4 contain')
                                    ->options($this->galleryPresetOptions())
                                    ->size('lg')
                                    ->rounding('lg')
                                    ->imageAspectRatio('3/4')
                                    ->imageFit('contain')
                                    ->gridColumns(2),
                            ]),
                    ]),
                Section::make('Dependent disabled options')
                    ->compact()
                    ->schema([
                        SelectField::make('image_choice_cards__tier')
                            ->label('Access tier')
                            ->helperText('Change tier to lock some yacht classes. Selected locked values clear automatically.')
                            ->options([
                                'a' => 'Tier A — locks superyacht & explorer',
                                'b' => 'Tier B — locks sailing yacht',
                                'c' => 'Tier C — all available',
                            ])
                            ->live(),
                        ImageChoiceCards::make('image_choice_cards__dependent')
                            ->label('Yacht class (reactive)')
                            ->options($this->yachtClassOptions())
                            ->multiple(false)
                            ->gridColumns(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 4])
                            ->disabledOptions(fn (Get $get): array => match ($get('image_choice_cards__tier')) {
                                'a' => ['superyacht', 'explorer'],
                                'b' => ['sailing_yacht'],
                                default => [],
                            })
                            ->live(),
                    ]),
                Section::make('Validation')
                    ->compact()
                    ->schema([
                        ImageChoiceCards::make('image_choice_cards__validation')
                            ->label('Required experience')
                            ->helperText('Leave empty and submit the playground form to see the required error.')
                            ->options($this->experienceOptions())
                            ->multiple(false)
                            ->gridColumns(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 4])
                            ->required(),
                    ]),
            ]);
    }

    /**
     * @return array<string, array{label: string, image: string, alt: string}>
     */
    protected function squareBodyOptions(): array
    {
        $images = ImageChoiceCardsPlaygroundSilhouettes::dataUris();

        return [
            'slim' => [
                'label' => 'Slim',
                'image' => $images['slim'],
                'alt' => 'Slim build',
            ],
            'average' => [
                'label' => 'Average',
                'image' => $images['average'],
                'alt' => 'Average build',
            ],
            'athletic' => [
                'label' => 'Athletic',
                'image' => $images['athletic'],
                'alt' => 'Athletic build',
            ],
            'shredded' => [
                'label' => 'Shredded',
                'image' => $images['shredded'],
                'alt' => 'Shredded build',
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, image: string, alt: string}>
     */
    protected function destinationOptions(): array
    {
        return [
            'amalfi' => [
                'label' => 'Amalfi Coast',
                'image' => $this->pexelsImage(1007657),
                'alt' => 'Amalfi Coast charter destination',
            ],
            'santorini' => [
                'label' => 'Santorini',
                'image' => $this->pexelsImage(2905956),
                'alt' => 'Santorini charter destination',
            ],
            'dubrovnik' => [
                'label' => 'Dubrovnik',
                'image' => $this->pexelsImage(4386239),
                'alt' => 'Dubrovnik charter destination',
            ],
            'ibiza' => [
                'label' => 'Ibiza',
                'image' => $this->pexelsImage(1565379),
                'alt' => 'Ibiza charter destination',
            ],
            'placeholder' => [
                'label' => 'No image',
                'image' => '',
                'alt' => 'Missing image placeholder',
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, image: string, alt: string}>
     */
    protected function focusOptions(): array
    {
        return [
            'yoga' => [
                'label' => 'Yoga',
                'image' => $this->pexelsImage(317157),
                'alt' => 'Yoga session',
            ],
            'strength' => [
                'label' => 'Strength',
                'image' => $this->pexelsImage(841130),
                'alt' => 'Strength training',
            ],
            'cardio' => [
                'label' => 'Cardio',
                'image' => $this->pexelsImage(3764011),
                'alt' => 'Cardio workout',
            ],
            'mobility' => [
                'label' => 'Mobility',
                'image' => $this->pexelsImage(4056535),
                'alt' => 'Mobility stretching',
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, image: string, alt: string}>
     */
    protected function galleryPresetOptions(): array
    {
        return [
            'warm' => [
                'label' => 'Warm sunset',
                'image' => $this->pexelsImage(1029600, 320, 320),
                'alt' => 'Warm sunset gallery preset',
            ],
            'cool' => [
                'label' => 'Cool ocean',
                'image' => $this->pexelsImage(457881, 320, 320),
                'alt' => 'Cool ocean gallery preset',
            ],
            'neutral' => [
                'label' => 'Neutral deck',
                'image' => $this->pexelsImage(1287145, 320, 320),
                'alt' => 'Neutral deck gallery preset',
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, image: string, alt: string}>
     */
    protected function yachtClassOptions(): array
    {
        return [
            'sailing_yacht' => [
                'label' => 'Sailing yacht',
                'image' => $this->pexelsImage(457878),
                'alt' => 'Sailing yacht',
            ],
            'catamaran' => [
                'label' => 'Catamaran',
                'image' => $this->pexelsImage(273886),
                'alt' => 'Catamaran',
            ],
            'motor_yacht' => [
                'label' => 'Motor yacht',
                'image' => $this->pexelsImage(1565379),
                'alt' => 'Motor yacht',
            ],
            'superyacht' => [
                'label' => 'Superyacht',
                'image' => $this->pexelsImage(33866367),
                'alt' => 'Superyacht',
            ],
            'explorer' => [
                'label' => 'Explorer',
                'image' => $this->pexelsImage(1268855),
                'alt' => 'Explorer yacht',
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, image: string, alt: string}>
     */
    protected function experienceOptions(): array
    {
        return [
            'sunset_cruise' => [
                'label' => 'Sunset cruise',
                'image' => $this->pexelsImage(1170572),
                'alt' => 'Sunset cruise experience',
            ],
            'diving' => [
                'label' => 'Diving',
                'image' => $this->pexelsImage(3757372),
                'alt' => 'Diving experience',
            ],
            'island_hopping' => [
                'label' => 'Island hopping',
                'image' => $this->pexelsImage(1371360),
                'alt' => 'Island hopping experience',
            ],
            'gourmet' => [
                'label' => 'Gourmet dining',
                'image' => $this->pexelsImage(1640777),
                'alt' => 'Gourmet dining experience',
            ],
        ];
    }

    protected function pexelsImage(int $photoId, int $width = 360, int $height = 480): string
    {
        return "https://images.pexels.com/photos/{$photoId}/pexels-photo-{$photoId}.jpeg?auto=compress&cs=tinysrgb&w={$width}&h={$height}&fit=crop";
    }
}
