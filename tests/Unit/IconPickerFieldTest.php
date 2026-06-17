<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\IconPickerField;

it('exposes icon picker configuration api', function () {
    $field = IconPickerField::make('icon')
        ->size('lg')
        ->variant('soft')
        ->clearable(false)
        ->sets(['heroicons', 'gravityui'])
        ->icons(['heroicon-o-star'])
        ->excludeIcons(['heroicon-o-x-mark'])
        ->searchResultsLayout('list')
        ->gridColumns(8)
        ->preload()
        ->limitPerSet(100)
        ->perPage(24);

    expect($field->getSize())->toBe('lg')
        ->and($field->getVariant())->toBe('soft')
        ->and($field->isClearable())->toBeFalse()
        ->and($field->getConfiguredSets())->toBe(['heroicons', 'gravityui'])
        ->and($field->getWhitelistedIcons())->toBe(['heroicon-o-star'])
        ->and($field->getExcludedIcons())->toBe(['heroicon-o-x-mark'])
        ->and($field->getSearchResultsLayout())->toBe('list')
        ->and($field->getGridColumns())->toBe(8)
        ->and($field->shouldPreload())->toBeTrue()
        ->and($field->getLimitPerSet())->toBe(100)
        ->and($field->getPerPage())->toBe(24);
});

it('defaults to bordered variant and maps legacy primary variant', function () {
    $field = IconPickerField::make('icon');

    expect($field->getVariant())->toBe('bordered');

    $field->variant('primary');

    expect($field->getVariant())->toBe('bordered');
});

it('exposes select-style wrapper classes', function () {
    $field = IconPickerField::make('icon')
        ->size('sm')
        ->variant('flat')
        ->default('heroicon-o-star');

    $classes = $field->getWrapperClasses();

    expect($classes)->toHaveKeys([
        'fff-select-field',
        'fff-select-field--sm',
        'fff-select-field--flat',
        'fi-color-primary',
        'fff-icon-picker-field',
        'fff-select-field--clearable-has-value',
    ]);
});

it('searches icons within configured sets', function () {
    $field = IconPickerField::make('icon')
        ->sets(['heroicons'])
        ->excludeIcons(['heroicon-o-x-mark']);

    $results = $field->searchIcons('star');

    expect(collect($results['icons'])->pluck('name'))->toContain('heroicon-o-star')
        ->and(collect($results['icons'])->pluck('name'))->not->toContain('heroicon-o-x-mark');
});

it('exposes layout shorthand and close on select configuration', function () {
    $field = IconPickerField::make('icon')
        ->iconsOnly()
        ->closeOnSelect(false);

    expect($field->getSearchResultsLayout())->toBe('icons')
        ->and($field->shouldCloseOnSelect())->toBeFalse();
});

it('validates selected icons against the configured catalog', function () {
    $field = IconPickerField::make('icon')
        ->sets(['heroicons'])
        ->icons(['heroicon-o-star', 'heroicon-o-heart']);

    expect($field->isAllowedIcon('heroicon-o-star'))->toBeTrue()
        ->and($field->isAllowedIcon('heroicon-o-bolt'))->toBeFalse();
});

it('renders svg previews for allowed icons', function () {
    $field = IconPickerField::make('icon')->sets(['heroicons']);

    $rendered = $field->renderIconSvgs(['heroicon-o-star', 'heroicon-o-not-real']);

    expect($rendered)->toHaveCount(1)
        ->and($rendered[0]['name'])->toBe('heroicon-o-star')
        ->and($rendered[0]['html'])->toContain('<svg');
});
