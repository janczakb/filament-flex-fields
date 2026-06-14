<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CountryField;
use Bjanczak\FilamentFlexFields\Support\Countries;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use InvalidArgumentException;

it('exposes country field configuration api', function () {
    $field = CountryField::make('country')
        ->size('lg')
        ->defaultCountry('US')
        ->countries(['US', 'PL', 'DE'])
        ->exceptCountries(['RU'])
        ->searchable(false)
        ->showCountryCode()
        ->showDialCode();

    expect($field->getSize())->toBe('lg')
        ->and($field->getDefaultCountryCode())->toBe('US')
        ->and($field->getAllowedCountryCodes())->toBe(['US', 'PL', 'DE'])
        ->and($field->getExceptCountryCodes())->toBe(['RU'])
        ->and($field->isSearchable())->toBeFalse()
        ->and($field->shouldShowCountryCode())->toBeTrue()
        ->and($field->shouldShowDialCode())->toBeTrue();
});

it('supports browser locale defaults and sorting', function () {
    $field = CountryField::make('country')
        ->countries(['PL', 'US'])
        ->browserLocaleDefault()
        ->browserLocaleSortFirst();

    expect($field->shouldUseBrowserLocaleDefault())->toBeTrue()
        ->and($field->shouldSortCountriesByBrowserLocale())->toBeTrue();
});

it('normalizes country state to uppercase iso code', function () {
    $field = CountryField::make('country')->defaultCountry('PL');

    expect($field->normalizeState('pl'))->toBe('PL')
        ->and($field->normalizeState('US'))->toBe('US')
        ->and($field->normalizeState(null))->toBeNull()
        ->and($field->normalizeState(''))->toBeNull();
});

it('falls back to default country for invalid codes', function () {
    $field = CountryField::make('country')->defaultCountry('PL');

    expect($field->normalizeState('XX'))->toBe('PL');
});

it('defaults to the full iso country list except excluded ones', function () {
    $field = CountryField::make('country')->exceptCountries(['RU', 'BY']);

    $codes = collect($field->getCountriesMetadata())->pluck('code')->all();

    expect($codes)->not->toContain('RU', 'BY')
        ->and($codes)->toContain('PL', 'US', 'AQ')
        ->and(count($codes))->toBe(count(Countries::allCodes()) - 2);
});

it('exposes country metadata and select options from countries support', function () {
    $field = CountryField::make('country')->countries(['PL', 'US']);

    expect($field->getCountriesMetadata())->toHaveCount(2)
        ->and($field->getCountriesMetadata()[0])->toHaveKeys(['code', 'name', 'dial_code', 'flag_url'])
        ->and($field->getCountrySelectOptions())->toHaveKeys(['PL', 'US'])
        ->and($field->getCountrySelectOptions()['PL'])->toHaveKeys(['label', 'image', 'description']);
});

it('sorts preferred country first when browser locale sort is enabled', function () {
    $field = CountryField::make('country')
        ->countries(['PL', 'US', 'DE'])
        ->browserLocaleSortFirst();

    $preferred = Countries::fromBrowserLocale(['PL', 'US', 'DE'], 'en-US');

    expect($field->getCountriesMetadata()[0]['code'])->toBe($preferred);
});

it('does not use laravel required rule on nullable state', function () {
    $field = CountryField::make('country')->required();

    expect($field->getRequiredValidationRule())->toBe('nullable')
        ->and($field->getValidationRules())->not->toContain('required');
});

it('requires a country when the field is required', function () {
    $field = CountryField::make('country')->required()->label('Country');

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('country', null, function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('validation.required', ['attribute' => 'Country']));
});

it('rejects countries outside the allowed list', function () {
    $field = CountryField::make('country')
        ->countries(['PL', 'US'])
        ->label('Country');

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('country', 'DE', function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('validation.in', ['attribute' => 'Country']));
});

it('registers country field playground variants', function () {
    $builder = app(FlexFieldsPlaygroundBuilder::class);
    $state = $builder->defaultState();

    expect($state)->toHaveKeys([
        'country__basic',
        'country__limited',
        'country__browser_locale',
    ]);
});

it('includes wrapper classes for size and variant', function () {
    $field = CountryField::make('country')
        ->size('sm')
        ->variant('secondary');

    expect($field->getWrapperClasses())->toBe([
        'fff-country-field',
        'fff-flex-text-input-field',
        'fff-country-field--sm',
        'fff-flex-text-input-field--sm',
        'fff-country-field--secondary',
        'fff-flex-text-input-field--secondary',
    ]);
});

it('exposes focus outline api', function () {
    expect(CountryField::make('country')->shouldShowFocusOutline())->toBeFalse()
        ->and(CountryField::make('country')->focusOutline()->shouldShowFocusOutline())->toBeTrue();
});

it('rejects unsupported country field variants', function () {
    CountryField::make('country')->variant('ghost')->getVariant();
})->throws(InvalidArgumentException::class);
