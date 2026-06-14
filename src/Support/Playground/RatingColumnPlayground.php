<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\RatingColumn;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Support\Icons\Heroicon;

class RatingColumnPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('RatingColumn')
                ->description('Read-only table column with the same star visuals as RatingField — supports fractional values, custom icons, colors, and sizes.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    View::make('filament-flex-fields::partials.playground.rating-column-demo')
                        ->viewData([
                            'rows' => $this->demoRows(),
                        ]),
                ]),
        ];
    }

    /**
     * @return list<array{title: string, score: string, satisfaction: string}>
     */
    protected function demoRows(): array
    {
        $scoreColumn = RatingColumn::make('score');

        $satisfactionColumn = RatingColumn::make('satisfaction')
            ->ratingIcon(Heroicon::Heart)
            ->ratingColor('danger')
            ->ratingSize('sm');

        $averageColumn = RatingColumn::make('average')
            ->stars(10)
            ->ratingColor('success')
            ->ratingSize('sm')
            ->showValue(false);

        return [
            [
                'title' => 'Harbor redesign',
                'score' => $scoreColumn->formatRatingDisplay(4),
                'satisfaction' => $satisfactionColumn->formatRatingDisplay(3.7),
                'average' => $averageColumn->formatRatingDisplay(8.2),
            ],
            [
                'title' => 'Fleet analytics',
                'score' => $scoreColumn->formatRatingDisplay(2.3),
                'satisfaction' => $satisfactionColumn->formatRatingDisplay(4.8),
                'average' => $averageColumn->formatRatingDisplay(6.5),
            ],
            [
                'title' => 'Guest feedback (pending)',
                'score' => $scoreColumn->formatRatingDisplay(null),
                'satisfaction' => $satisfactionColumn->formatRatingDisplay(null),
                'average' => $averageColumn->formatRatingDisplay(null),
            ],
        ];
    }
}
