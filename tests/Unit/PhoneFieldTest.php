<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\PhoneCountries;
use Filament\Support\Icons\Heroicon;

it('exposes phone field configuration api', function () {
    $field = PhoneField::make('phone')
        ->size('lg')
        ->defaultCountry('US')
        ->countries(['US', 'PL', 'DE'])
        ->exceptCountries(['RU'])
        ->searchable(false)
        ->suffixIcon(false)
        ->internationalPrefix(false)
        ->mobileOnly();

    expect($field->getSize())->toBe('lg')
        ->and($field->getDefaultCountryCode())->toBe('US')
        ->and($field->getAllowedCountryCodes())->toBe(['US', 'PL', 'DE'])
        ->and($field->getExceptCountryCodes())->toBe(['RU'])
        ->and($field->isSearchable())->toBeFalse()
        ->and($field->hasSuffixIcon())->toBeFalse()
        ->and($field->showsInternationalPrefix())->toBeFalse()
        ->and($field->isMobileOnly())->toBeTrue();
});

it('accepts custom suffix icons from any icon set', function () {
    $field = PhoneField::make('phone')->suffixIcon('ri-phone-line');

    expect($field->getSuffixIcon())->toBe('ri-phone-line')
        ->and($field->hasSuffixIcon())->toBeTrue();
});

it('defaults to the gravity ui smartphone icon when no custom suffix icon is set', function () {
    $field = PhoneField::make('phone');

    expect($field->getSuffixIcon())->toBe(GravityIcon::Smartphone)
        ->and($field->getDefaultSuffixIcon())->toBe(GravityIcon::Smartphone);
});

it('allows overriding the default suffix icon with heroicon or any icon set', function () {
    $field = PhoneField::make('phone')->suffixIcon(Heroicon::OutlinedDevicePhoneMobile);

    expect($field->getSuffixIcon())->toBe(Heroicon::OutlinedDevicePhoneMobile);
});

it('reads the default suffix icon from config', function () {
    config(['filament-flex-fields.ui.phone_suffix_icon' => 'heroicon-o-phone']);

    expect(PhoneField::make('phone')->getDefaultSuffixIcon())->toBe('heroicon-o-phone');
});

it('supports a custom placeholder', function () {
    $field = PhoneField::make('phone')->placeholder('Mobile number');

    expect($field->getPlaceholder())->toBe('Mobile number');
});

it('supports read only mode', function () {
    $field = PhoneField::make('phone')->readOnly();

    expect($field->isReadOnly())->toBeTrue();
});

it('defaults to all supported countries except excluded ones', function () {
    $field = PhoneField::make('phone')->exceptCountries(['RU', 'BY']);

    $codes = collect($field->getCountriesMetadata())->pluck('code')->all();

    expect($codes)->not->toContain('RU', 'BY')
        ->and($codes)->toContain('PL', 'US');
});

it('normalizes phone state from array input', function () {
    $field = PhoneField::make('phone')->defaultCountry('PL');

    expect($field->normalizeState([
        'country' => 'PL',
        'national' => '123456789',
        'e164' => '',
    ]))->toMatchArray([
        'country' => 'PL',
        'national' => '123456789',
        'e164' => '+48123456789',
    ]);
});

it('normalizes phone state from e164 string', function () {
    $field = PhoneField::make('phone')->defaultCountry('PL');

    expect($field->normalizeState('+48123456789'))->toMatchArray([
        'country' => 'PL',
        'national' => '123456789',
        'e164' => '+48123456789',
    ]);
});

it('uses translated country names from countries file', function () {
    expect(PhoneCountries::name('PL'))->toBe(__('filament-flex-fields::countries.PL'))
        ->and(PhoneCountries::flagUrl('PL'))->toBe('https://cdn.jsdelivr.net/gh/HatScripts/circle-flags@latest/flags/pl.svg');
});

it('uses circle-flags slug overrides for territories without direct svg', function () {
    expect(PhoneCountries::flagUrl('AC'))->toBe('https://cdn.jsdelivr.net/gh/HatScripts/circle-flags@latest/flags/sh-ac.svg')
        ->and(PhoneCountries::flagUrl('BQ'))->toBe('https://cdn.jsdelivr.net/gh/HatScripts/circle-flags@latest/flags/bq-bo.svg');
});

it('maps browser locale to country code', function () {
    expect(PhoneCountries::fromBrowserLocale(null, 'pl-PL'))->toBe('PL')
        ->and(PhoneCountries::fromBrowserLocale(['PL', 'US'], 'en-US'))->toBe('US')
        ->and(PhoneCountries::fromBrowserLocale(['PL', 'DE'], 'de'))->toBe('DE');
});

it('sorts preferred country first in metadata list', function () {
    $countries = PhoneCountries::metadata(['PL', 'US', 'DE']);
    $sorted = PhoneCountries::sortWithPreferredFirst($countries, 'US');

    expect($sorted[0]['code'])->toBe('US')
        ->and(collect($sorted)->pluck('code')->all())->toBe(['US', 'DE', 'PL']);
});

it('formats national display for server-side rendering', function () {
    expect(PhoneCountries::formatNationalDisplay('512345678', 'PL'))->toBe('512 345 678');
    expect(PhoneCountries::formatNationalDisplay('612345678', 'FR'))->toBe('06 12 34 56 78');
});

it('builds rich select options for countries', function () {
    $options = PhoneCountries::selectOptions(['PL', 'US']);

    expect($options)->toHaveKeys(['PL', 'US'])
        ->and($options['PL'])->toHaveKeys(['label', 'image', 'description']);
});

it('does not use laravel required rule on the composite state', function () {
    $field = PhoneField::make('phone')->required();

    expect($field->getRequiredValidationRule())->toBe('nullable')
        ->and($field->getValidationRules())->not->toContain('required');
});

it('requires a phone number when the field is required', function () {
    $field = PhoneField::make('phone')->required()->label('Phone number');

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('phone', [
        'country' => 'PL',
        'national' => '',
        'e164' => '',
    ], function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('validation.required', ['attribute' => 'Phone number']));
});

it('rejects invalid phone numbers', function () {
    $field = PhoneField::make('phone');

    expect($field->getPhoneValidationMessage([
        'country' => 'PL',
        'national' => '123',
        'e164' => '+48123',
    ]))->toBe(__('filament-flex-fields::default.validation.phone.invalid'));
});

it('exposes rich country options for the picker', function () {
    $field = PhoneField::make('phone')->countries(['PL', 'US']);

    expect($field->getCountrySelectOptions())->toHaveKeys(['PL', 'US'])
        ->and($field->getCountrySelectOptions()['PL'])->toHaveKeys(['label', 'image', 'description']);
});

it('registers phone field playground variants', function () {
    $builder = app(FlexFieldsPlaygroundBuilder::class);
    $state = $builder->defaultState();

    expect($state)->toHaveKeys([
        'phone__basic',
        'phone__invalid',
        'phone__limited',
        'phone__mobile_only',
        'phone__browser_locale',
    ]);
});

it('merges phone playground state into the builder', function () {
    $builder = app(FlexFieldsPlaygroundBuilder::class);
    $state = $builder->defaultState();

    expect($state['phone__basic']['country'])->toBe('PL')
        ->and($state['phone__basic']['national'])->not->toBe('');
});

it('includes wrapper classes for size and variant', function () {
    $field = PhoneField::make('phone')
        ->size('sm')
        ->variant('secondary');

    expect($field->getWrapperClasses())->toBe([
        'fff-phone-field',
        'fff-flex-text-input-field',
        'fff-phone-field--sm',
        'fff-flex-text-input-field--sm',
        'fff-phone-field--secondary',
        'fff-flex-text-input-field--secondary',
    ]);
});

it('exposes focus outline api', function () {
    expect(PhoneField::make('phone')->shouldShowFocusOutline())->toBeFalse()
        ->and(PhoneField::make('phone')->focusOutline()->shouldShowFocusOutline())->toBeTrue();
});

it('rejects unsupported phone field variants', function () {
    PhoneField::make('phone')->variant('ghost')->getVariant();
})->throws(InvalidArgumentException::class);

it('rejects unsupported mobile and fixed line combination', function () {
    $field = PhoneField::make('phone')->mobileOnly()->fixedLineOnly();

    $field->getPhoneValidationMessage([
        'country' => 'PL',
        'national' => '512345678',
        'e164' => '+48512345678',
    ]);
})->throws(InvalidArgumentException::class);
