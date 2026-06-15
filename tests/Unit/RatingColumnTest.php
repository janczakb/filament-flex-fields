<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\RatingColumn;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use Bjanczak\FilamentFlexFields\Support\Playground\RatingColumnPlayground;
use Bjanczak\FilamentFlexFields\Support\RatingColumnRenderCache;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;

function makeRatingColumnTestIcon(string $class = 'fff-test-rating-icon'): HtmlString
{
    return new HtmlString('<svg class="'.$class.'" aria-hidden="true"></svg>');
}

it('extends text column and formats integer ratings with default stars', function () {
    $column = RatingColumn::make('score')
        ->ratingIcon(makeRatingColumnTestIcon());

    $html = $column->formatRatingDisplay(4);

    expect($html)
        ->toContain('fff-rating-column')
        ->toContain('fff-rating')
        ->toContain('fff-rating--md')
        ->toContain('fi-color-warning')
        ->toContain('is-read-only')
        ->toContain('fff-rating--with-value')
        ->toContain('fff-rating__value')
        ->toContain('4.0');
});

it('formats fractional ratings with partial star fill', function () {
    $column = RatingColumn::make('score')
        ->ratingIcon(makeRatingColumnTestIcon());

    $html = $column->formatRatingDisplay(3.7);

    expect($html)
        ->toContain('fff-rating__icon-clip')
        ->toContain('style="width: 70%"')
        ->toContain('3.7');
});

it('supports custom icons colors sizes and max rating', function () {
    $column = RatingColumn::make('satisfaction')
        ->stars(10)
        ->ratingSize(ControlSize::Lg)
        ->ratingColor('success')
        ->ratingIcon(makeRatingColumnTestIcon('fff-test-rating-icon-heart'))
        ->showValue(false);

    $html = $column->formatRatingDisplay(8.2);

    expect($html)
        ->toContain('fff-rating--lg')
        ->toContain('fi-color-success')
        ->not->toContain('fff-rating__value')
        ->not->toContain('fff-rating--with-value');
});

it('returns empty string for blank or invalid state', function () {
    $column = RatingColumn::make('score');

    expect($column->formatRatingDisplay(null))->toBe('')
        ->and($column->formatRatingDisplay(''))->toBe('')
        ->and($column->formatRatingDisplay('not-a-number'))->toBe('');
});

it('normalizes numeric state and clamps to max', function () {
    $column = RatingColumn::make('score')->stars(5);

    expect($column->normalizeRatingFromState(3.7))->toEqualWithDelta(3.7, 0.0001)
        ->and($column->normalizeRatingFromState('4'))->toBe(4.0)
        ->and($column->normalizeRatingFromState(7))->toBe(5.0)
        ->and($column->normalizeRatingFromState(-1))->toBeNull();
});

it('shares fill percentage logic with rating field', function () {
    $column = RatingColumn::make('score');

    expect($column->getFillPercentageForValue(3.7, 1))->toBe(1.0)
        ->and($column->getFillPercentageForValue(3.7, 4))->toEqualWithDelta(0.7, 0.0001)
        ->and($column->getFillPercentageForValue(3.7, 5))->toBe(0.0)
        ->and($column->getFillPercentageForValue(null, 1))->toBe(0.0);
});

it('caches identical rating renders within the same request', function () {
    RatingColumnRenderCache::flush();

    $column = RatingColumn::make('score')
        ->ratingIcon(makeRatingColumnTestIcon());

    $column->formatRatingDisplay(4);
    $column->formatRatingDisplay(4);

    expect(RatingColumnRenderCache::entries())->toHaveCount(1);
});

it('registers rating column playground section after rating field', function () {
    $builder = app(FlexFieldsPlaygroundBuilder::class);
    $components = $builder->build();

    $sectionHeadings = collect($components)
        ->filter(fn ($component): bool => $component instanceof Section)
        ->map(fn (Section $section): string => (string) $section->getHeading())
        ->values()
        ->all();

    $ratingIndex = array_search('Rating', $sectionHeadings, true);
    $ratingColumnIndex = array_search('RatingColumn', $sectionHeadings, true);

    expect($ratingIndex)->not->toBeFalse()
        ->and($ratingColumnIndex)->not->toBeFalse()
        ->and($ratingColumnIndex)->toBeGreaterThan($ratingIndex);
});

it('renders rating column playground demo rows with stars and empty cells', function () {
    $playground = app(RatingColumnPlayground::class);
    $icon = makeRatingColumnTestIcon();
    $scoreColumn = RatingColumn::make('score')->ratingIcon($icon);
    $satisfactionColumn = RatingColumn::make('satisfaction')
        ->ratingIcon($icon)
        ->ratingColor('danger')
        ->ratingSize('sm');

    expect($scoreColumn->formatRatingDisplay(4))->toContain('fff-rating-column')
        ->and($satisfactionColumn->formatRatingDisplay(3.7))->toContain('style="width: 70%"')
        ->and($scoreColumn->formatRatingDisplay(null))->toBe('');

    $section = collect($playground->components())->first();

    expect($section)->not->toBeNull()
        ->and($section->getHeading())->toBe('RatingColumn');
});
