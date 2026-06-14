<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;
use Bjanczak\FilamentFlexFields\Support\CurrencyCountries;
use Bjanczak\FilamentFlexFields\Support\FlexFieldFormBuilder;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;

it('exposes currency field configuration api', function () {
    $field = CurrencyField::make('price')
        ->size('lg')
        ->currency('EUR')
        ->currencies(['EUR', 'USD', 'PLN'])
        ->locale('de_DE')
        ->min(0)
        ->max(999999.99)
        ->allowNegative()
        ->animated(false)
        ->commitDecimalsOnBlur(false)
        ->searchable(false);

    expect($field->getSize())->toBe('lg')
        ->and($field->getDefaultCurrencyCode())->toBe('EUR')
        ->and($field->getAllowedCurrencyCodes())->toBe(['EUR', 'PLN', 'USD'])
        ->and($field->getLocale())->toBe('de_DE')
        ->and($field->getMin())->toBe(0.0)
        ->and($field->getMax())->toBe(999999.99)
        ->and($field->getMinMinorUnits('EUR'))->toBe(0)
        ->and($field->getMaxMinorUnits('EUR'))->toBe(99_999_999)
        ->and($field->allowsNegative())->toBeTrue()
        ->and($field->isAnimated())->toBeFalse()
        ->and($field->shouldCommitDecimalsOnBlur())->toBeFalse()
        ->and($field->isSearchable())->toBeFalse()
        ->and($field->hasCurrencySelect())->toBeTrue();
});

it('supports soft variant for gray shell without shadow', function () {
    $field = CurrencyField::make('price')->variant('soft');

    expect($field->getVariant())->toBe('soft')
        ->and($field->getWrapperClasses())->toContain('fff-flex-text-input-field--soft');
});

it('rejects unsupported currency field variants', function () {
    CurrencyField::make('price')->variant('ghost')->getVariant();
})->throws(InvalidArgumentException::class);

it('uses fixed currency mode without currency select by default', function () {
    $field = CurrencyField::make('price')->currency('PLN');

    expect($field->hasCurrencySelect())->toBeFalse()
        ->and($field->getAllowedCurrencyCodes())->toBeNull()
        ->and($field->getDefaultCurrencyCode())->toBe('PLN');
});

it('normalizes single-currency state as minor units', function () {
    $field = CurrencyField::make('price')->currency('PLN');

    expect($field->normalizeState(6_666_660))->toBe(6_666_660)
        ->and($field->normalizeState(['amount' => 1_250]))->toBe(1_250)
        ->and($field->normalizeState(null))->toBeNull()
        ->and($field->normalizeState(12.50))->toBe(1_250);
});

it('normalizes multi-currency state as amount and currency array', function () {
    $field = CurrencyField::make('price')
        ->currency('EUR')
        ->currencies(['EUR', 'USD']);

    expect($field->normalizeState(1_250))->toMatchArray([
        'amount' => 1_250,
        'currency' => 'EUR',
    ])->and($field->normalizeState([
        'amount' => 500,
        'currency' => 'USD',
    ]))->toMatchArray([
        'amount' => 500,
        'currency' => 'USD',
    ])->and($field->normalizeState([
        'amount' => null,
        'currency' => 'GBP',
    ]))->toMatchArray([
        'amount' => null,
        'currency' => 'EUR',
    ]);
});

it('converts major units to minor units for validation bounds', function () {
    $field = CurrencyField::make('price')->currency('PLN')->min(10.50)->max(100);

    expect($field->getMinMinorUnits())->toBe(1_050)
        ->and($field->getMaxMinorUnits())->toBe(10_000);
});

it('handles zero-decimal currencies like jpy', function () {
    expect(CurrencyCountries::decimals('JPY'))->toBe(0)
        ->and(CurrencyCountries::toMinorUnits(1500, 'JPY'))->toBe(1500)
        ->and(CurrencyCountries::toMajorUnits(1500, 'JPY'))->toBe(1500.0);
});

it('exposes currency metadata and select options', function () {
    $field = CurrencyField::make('price')->currencies(['PLN', 'EUR']);

    expect($field->getCurrenciesMetadata())->toHaveCount(2)
        ->and($field->getCurrenciesMetadata()[0])->toHaveKeys(['code', 'symbol', 'name', 'decimals', 'locale'])
        ->and($field->getCurrencySelectOptions())->toHaveKeys(['PLN', 'EUR']);
});

it('does not use laravel required rule on composite state', function () {
    $field = CurrencyField::make('price')
        ->currency('PLN')
        ->currencies(['PLN', 'EUR'])
        ->required();

    expect($field->getRequiredValidationRule())->toBe('nullable')
        ->and($field->getValidationRules())->not->toContain('required');
});

it('requires an amount when the field is required', function () {
    $field = CurrencyField::make('price')
        ->currency('PLN')
        ->required()
        ->label('Price');

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('price', null, function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('validation.required', ['attribute' => 'Price']));
});

it('rejects amounts below min in major units', function () {
    $field = CurrencyField::make('price')->currency('PLN')->min(10);

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('price', 500, function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.currency.min', [
        'min' => CurrencyCountries::formatMajor(10, 'PLN'),
    ]));
});

it('rejects negative amounts when not allowed', function () {
    $field = CurrencyField::make('price')->currency('PLN');

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('price', -100, function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.currency.negative'));
});

it('includes wrapper classes for size', function () {
    $field = CurrencyField::make('price')->size('sm');

    expect($field->getWrapperClasses())->toBe([
        'fff-currency-field',
        'fff-flex-text-input-field',
        'fff-currency-field--sm',
        'fff-flex-text-input-field--sm',
        'fff-currency-field--primary',
        'fff-flex-text-input-field--primary',
    ]);
});

it('builds currency field from flex field definition', function () {
    $builder = new FlexFieldFormBuilder;

    $component = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'budget',
            'label' => 'Budget',
            'type' => FieldType::Currency->value,
            'config' => [
                'currency' => 'PLN',
                'locale' => 'pl_PL',
                'currencies' => ['PLN', 'EUR'],
                'min' => 0,
                'max' => 50000,
                'size' => 'lg',
            ],
        ]),
    );

    expect($component)->toBeInstanceOf(CurrencyField::class)
        ->and($component->getDefaultCurrencyCode())->toBe('PLN')
        ->and($component->getLocale())->toBe('pl_PL')
        ->and($component->getAllowedCurrencyCodes())->toBe(['EUR', 'PLN'])
        ->and($component->getMin())->toBe(0.0)
        ->and($component->getMax())->toBe(50000.0)
        ->and($component->getSize())->toBe('lg');
});

it('registers currency field playground variants', function () {
    $builder = app(FlexFieldsPlaygroundBuilder::class);
    $state = $builder->defaultState();

    expect($state)->toHaveKeys([
        'currency__pln',
        'currency__eur_usd',
        'currency__limited',
    ]);
});

it('rejects unsupported currency codes in metadata', function () {
    CurrencyCountries::symbol('XYZ');
})->throws(InvalidArgumentException::class);

it('supports popular currency codes out of the box', function () {
    expect(CurrencyCountries::allSupportedCodes())->toHaveCount(35)
        ->and(CurrencyCountries::allSupportedCodes())->toContain('CHF', 'CAD', 'CZK', 'UAH', 'CNY')
        ->and(CurrencyCountries::decimals('KRW'))->toBe(0)
        ->and(CurrencyCountries::decimals('HUF'))->toBe(0)
        ->and(CurrencyCountries::symbol('INR'))->toBe('₹')
        ->and(CurrencyCountries::resolve(['PLN', 'CHF', 'XYZ']))->toBe(['CHF', 'PLN']);
});

it('merges custom currencies from config', function () {
    config([
        'filament-flex-fields.currencies' => [
            'VND' => [
                'symbol' => '₫',
                'name' => 'Vietnamese dong',
                'decimals' => 0,
                'locale' => 'vi-VN',
            ],
        ],
    ]);

    $field = CurrencyField::make('price')
        ->currency('VND')
        ->currencies(['VND', 'EUR']);

    expect(CurrencyCountries::isSupported('VND'))->toBeTrue()
        ->and(CurrencyCountries::allSupportedCodes())->toContain('VND')
        ->and(CurrencyCountries::symbol('VND'))->toBe('₫')
        ->and(CurrencyCountries::toMinorUnits(1500, 'VND'))->toBe(1500)
        ->and($field->getAllowedCurrencyCodes())->toBe(['EUR', 'VND'])
        ->and($field->normalizeState(99_000))->toMatchArray([
            'amount' => 99_000,
            'currency' => 'VND',
        ]);
})->after(fn () => config(['filament-flex-fields.currencies' => []]));

it('allows config to override built-in currency symbol', function () {
    config([
        'filament-flex-fields.currencies' => [
            'PLN' => [
                'symbol' => 'PLN',
                'name' => 'Custom złoty label',
                'decimals' => 2,
                'locale' => 'pl-PL',
            ],
        ],
    ]);

    expect(CurrencyCountries::symbol('PLN'))->toBe('PLN')
        ->and(collect(CurrencyCountries::metadata(['PLN']))->firstWhere('code', 'PLN')['symbol'])->toBe('PLN');
})->after(fn () => config(['filament-flex-fields.currencies' => []]));

it('rejects invalid custom currency config', function () {
    config([
        'filament-flex-fields.currencies' => [
            'VND' => [
                'symbol' => '₫',
                'name' => 'Vietnamese dong',
            ],
        ],
    ]);

    CurrencyCountries::symbol('VND');
})->throws(InvalidArgumentException::class)
    ->after(fn () => config(['filament-flex-fields.currencies' => []]));

it('marks currency as a custom component field type', function () {
    expect(FieldType::Currency->isCustomComponent())->toBeTrue();
});

it('builds initial display segments for server-side rendering', function () {
    $field = CurrencyField::make('price')
        ->currency('PLN')
        ->locale('pl_PL');

    $display = $field->getInitialDisplay(6_666_660);

    expect($display['isEmpty'])->toBeFalse()
        ->and($display['symbol'])->toBe('zł')
        ->and($display['currencyCode'])->toBe('PLN')
        ->and(collect($display['segments'])->pluck('char')->implode(''))->toBe('66 666,6');
});

it('builds empty initial display for null amount', function () {
    $field = CurrencyField::make('price')
        ->currency('PLN');

    expect($field->getInitialDisplay(null))->toMatchArray([
        'isEmpty' => true,
        'negative' => false,
        'segments' => [],
        'symbol' => 'zł',
        'currencyCode' => 'PLN',
    ]);
});

it('matches js edit state hydration for fractional amounts', function () {
    expect(CurrencyCountries::editStateFromMinor(6_666_660, 2))->toMatchArray([
        'wholeDigits' => '66666',
        'fracDigits' => '6',
        'inDecimal' => false,
        'negative' => false,
    ]);
});

it('serializes toggled negative sign back to minor units', function () {
    $edit = CurrencyCountries::editStateFromMinor(-1_250, 2);

    $edit['negative'] = false;

    expect(CurrencyCountries::minorFromEditState($edit, 2))->toBe(1_250);

    $edit['negative'] = true;

    expect(CurrencyCountries::minorFromEditState($edit, 2))->toBe(-1_250);
});

it('exposes negative min bound as minor units', function () {
    $field = CurrencyField::make('price')
        ->currency('PLN')
        ->allowNegative()
        ->min(-1000)
        ->max(1000);

    expect($field->getMinMinorUnits())->toBe(-100_000)
        ->and($field->getMaxMinorUnits())->toBe(100_000);
});
