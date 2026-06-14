<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RatingField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class RatingPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'rating__default' => 3,
            'rating__with_label' => 4,
            'rating__heart' => 3,
            'rating__heart_filled' => 3,
            'rating__sm' => 3,
            'rating__md' => 3,
            'rating__lg' => 3,
            'rating__accent' => 4,
            'rating__danger' => 4,
            'rating__success' => 4,
            'rating__disabled' => 3,
            'rating__required' => null,
            'rating__readonly_15' => 1.5,
            'rating__readonly_23' => 2.3,
            'rating__readonly_37' => 3.7,
            'rating__readonly_42' => 4.2,
            'rating__readonly_48' => 4.8,
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Rating')
                ->description('SaaS-style rating input with custom icons, colors, sizes, disabled, required, and fractional read-only display. See the RatingColumn section below for read-only table examples.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    RatingField::make('rating__default')
                        ->label('Rating')
                        ->helperText('Default stars with hover preview.'),
                    RatingField::make('rating__with_label')
                        ->label('How would you rate this product?')
                        ->helperText('With label variant from SaaS docs.'),
                    RatingField::make('rating__heart')
                        ->label('Custom icon heart')
                        ->icon(Heroicon::OutlinedHeart)
                        ->default(3),
                    RatingField::make('rating__heart_filled')
                        ->label('Custom icon per item')
                        ->icon(Heroicon::Heart)
                        ->color('danger')
                        ->default(3),
                    Grid::make(['default' => 1, 'sm' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            RatingField::make('rating__sm')
                                ->label('Small')
                                ->size('sm'),
                            RatingField::make('rating__md')
                                ->label('Medium'),
                            RatingField::make('rating__lg')
                                ->label('Large')
                                ->size('lg'),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            RatingField::make('rating__accent')
                                ->label('Accent')
                                ->color('primary'),
                            RatingField::make('rating__danger')
                                ->label('Danger')
                                ->color('danger'),
                            RatingField::make('rating__success')
                                ->label('Success')
                                ->color('success'),
                        ]),
                    RatingField::make('rating__disabled')
                        ->label('Disabled')
                        ->disabled(),
                    RatingField::make('rating__required')
                        ->label('Required rating')
                        ->required()
                        ->helperText('Must select a rating before saving.'),
                    Section::make('Read only')
                        ->compact()
                        ->schema([
                            RatingField::make('rating__readonly_15')
                                ->hiddenLabel()
                                ->readOnly(),
                            RatingField::make('rating__readonly_23')
                                ->hiddenLabel()
                                ->readOnly(),
                            RatingField::make('rating__readonly_37')
                                ->hiddenLabel()
                                ->readOnly(),
                            RatingField::make('rating__readonly_42')
                                ->hiddenLabel()
                                ->readOnly(),
                            RatingField::make('rating__readonly_48')
                                ->hiddenLabel()
                                ->readOnly(),
                        ]),
                ]),
        ];
    }
}
