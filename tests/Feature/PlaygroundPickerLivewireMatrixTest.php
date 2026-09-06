<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CountryField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TagsField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TimezoneField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Bjanczak\FilamentFlexFields\Support\Playground\AddressAutocompletePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\CountryFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\CurrencyFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexFieldsPlaygroundStore;
use Bjanczak\FilamentFlexFields\Support\Playground\PhoneFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\TagsFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\TimezoneFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\UserSelectPlayground;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

/**
 * @return array<string, array{0: class-string, 1: string}>
 */
function pickerPlaygroundCases(): array
{
    return [
        'timezone-field' => [TimezoneFieldPlayground::class, 'timezone-field'],
        'country-field' => [CountryFieldPlayground::class, 'country-field'],
        'phone-field' => [PhoneFieldPlayground::class, 'phone-field'],
        'address-autocomplete' => [AddressAutocompletePlayground::class, 'address-autocomplete'],
        'tags-field' => [TagsFieldPlayground::class, 'tags-field'],
        'currency-field' => [CurrencyFieldPlayground::class, 'currency-field'],
        'user-select' => [UserSelectPlayground::class, 'user-select'],
    ];
}

it('hydrates related playground defaultState keys after mount', function (string $class, string $slug): void {
    $playground = app($class);
    $defaults = $playground->defaultState();

    TestableTranslatableForm::$formSchema = $playground->components();

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->call('mountWithPlaygroundState', $defaults);

    $data = $livewire->get('data');

    foreach (array_keys($defaults) as $key) {
        expect($data)->toHaveKey($key);
    }
})->with([
    'timezone-field' => [TimezoneFieldPlayground::class, 'timezone-field'],
    'country-field' => [CountryFieldPlayground::class, 'country-field'],
    'phone-field' => [PhoneFieldPlayground::class, 'phone-field'],
    'address-autocomplete' => [AddressAutocompletePlayground::class, 'address-autocomplete'],
    'tags-field' => [TagsFieldPlayground::class, 'tags-field'],
    'currency-field' => [CurrencyFieldPlayground::class, 'currency-field'],
]);

it('persists related playground defaultState blobs', function (string $class, string $slug): void {
    Cache::flush();
    auth()->login(new GenericUser(['id' => 91]));

    $defaults = app($class)->defaultState();
    $store = new FlexFieldsPlaygroundStore;

    $store->put($slug, $defaults);
    expect($store->get($slug))->toBe($defaults);

    $store->forget($slug);
    expect($store->get($slug))->toBeNull();
})->with(pickerPlaygroundCases());

it('persists each related playground key atomically', function (string $class, string $slug): void {
    Cache::flush();
    auth()->login(new GenericUser(['id' => 92]));

    $defaults = app($class)->defaultState();
    $store = new FlexFieldsPlaygroundStore;

    foreach ($defaults as $key => $value) {
        $store->put($slug, [$key => $value]);
        expect($store->get($slug))->toBe([$key => $value]);
    }
})->with(pickerPlaygroundCases());

it('timezone field conflict hammer through Livewire', function (): void {
    TestableTranslatableForm::$formSchema = [
        TimezoneField::make('timezone__basic')
            ->defaultTimezone('Europe/Warsaw')
            ->live(),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class);

    foreach (['Europe/Warsaw', 'UTC', 'America/New_York', null, 'Asia/Tokyo', 'Europe/Berlin'] as $tz) {
        $livewire->set('data.timezone__basic', $tz)
            ->assertSet('data.timezone__basic', $tz);
    }
});

it('country field conflict hammer through Livewire', function (): void {
    TestableTranslatableForm::$formSchema = [
        CountryField::make('country__basic')
            ->defaultCountry('PL')
            ->live(),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class);

    foreach (['PL', 'US', 'DE', null, 'GB', 'FR', 'PL'] as $code) {
        $livewire->set('data.country__basic', $code)
            ->assertSet('data.country__basic', $code);
    }
});

it('tags field reorder and clear cycles', function (): void {
    TestableTranslatableForm::$formSchema = [
        TagsField::make('tags_field__reorderable')
            ->reorderable()
            ->live(),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class);

    foreach ([
        ['alpha', 'beta', 'gamma'],
        ['gamma', 'beta', 'alpha'],
        [],
        ['laravel'],
        ['filament', 'pest', 'php'],
    ] as $tags) {
        $livewire->set('data.tags_field__reorderable', $tags)
            ->assertSet('data.tags_field__reorderable', $tags);
    }
});

it('phone field country picker state round-trips e164', function (): void {
    $playground = app(PhoneFieldPlayground::class);
    TestableTranslatableForm::$formSchema = $playground->components();

    $next = [
        'country' => 'US',
        'national' => '2025551234',
        'e164' => '+12025551234',
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->call('mountWithPlaygroundState', $playground->defaultState())
        ->set('data.phone__basic', $next);

    expect($livewire->get('data.phone__basic.country'))->toBe('US')
        ->and($livewire->get('data.phone__basic.e164'))->toBe('+12025551234');
});

it('currency multi-currency picker state round-trips', function (): void {
    TestableTranslatableForm::$formSchema = [
        CurrencyField::make('currency__eur_usd')
            ->currencies(['EUR', 'USD', 'GBP', 'PLN'])
            ->currency('EUR')
            ->live(),
    ];

    $next = [
        'amount' => 99_00,
        'currency' => 'USD',
    ];

    Livewire::test(TestableTranslatableForm::class)
        ->set('data.currency__eur_usd', $next)
        ->assertSet('data.currency__eur_usd', $next)
        ->set('data.currency__eur_usd', [
            'amount' => 12_50,
            'currency' => 'EUR',
        ])
        ->assertSet('data.currency__eur_usd.currency', 'EUR')
        ->assertSet('data.currency__eur_usd.amount', 12_50);
});

it('user select mock options single and multiple round-trips without DB', function (): void {
    $users = [
        'jane' => [
            'label' => 'Jane Cooper',
            'userName' => 'Jane Cooper',
            'userEmail' => 'jane@example.com',
            'userVerified' => true,
        ],
        'alex' => [
            'label' => 'Alex Rivera',
            'userName' => 'Alex Rivera',
            'userEmail' => 'alex@example.com',
            'userVerified' => false,
        ],
        'sam' => [
            'label' => 'Sam Patel',
            'userName' => 'Sam Patel',
            'userEmail' => 'sam@example.com',
            'userVerified' => true,
        ],
    ];

    TestableTranslatableForm::$formSchema = [
        UserSelect::make('user_select__single')
            ->options($users)
            ->searchable()
            ->live(),
        UserSelect::make('user_select__multiple')
            ->options($users)
            ->multiple()
            ->searchable()
            ->live(),
    ];

    Livewire::test(TestableTranslatableForm::class)
        ->set('data.user_select__single', 'alex')
        ->assertSet('data.user_select__single', 'alex')
        ->set('data.user_select__multiple', ['jane', 'sam'])
        ->assertSet('data.user_select__multiple', ['jane', 'sam'])
        ->set('data.user_select__multiple', [])
        ->assertSet('data.user_select__multiple', [])
        ->set('data.user_select__single', 'jane')
        ->assertSet('data.user_select__single', 'jane');
});

it('address autocomplete structured and string formats round-trip', function (): void {
    $playground = app(AddressAutocompletePlayground::class);
    TestableTranslatableForm::$formSchema = $playground->components();

    $structured = [
        'street' => 'ul. Marszałkowska',
        'city' => 'Warszawa',
        'postcode' => '00-001',
        'country' => 'PL',
        'country_name' => 'Polska',
        'place_name' => 'ul. Marszałkowska, 00-001 Warszawa, Polska',
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->call('mountWithPlaygroundState', $playground->defaultState())
        ->set('data.address_autocomplete__full', $structured)
        ->set('data.address_autocomplete__string', 'Gdańsk, Polska');

    expect($livewire->get('data.address_autocomplete__full.city'))->toBe('Warszawa')
        ->and($livewire->get('data.address_autocomplete__full.country'))->toBe('PL')
        ->and($livewire->get('data.address_autocomplete__string'))->toBe('Gdańsk, Polska');
});

it('timezone and country playground defaultState key inventory', function (): void {
    expect(app(TimezoneFieldPlayground::class)->defaultState())->toHaveKeys([
        'timezone__basic',
        'timezone__empty',
        'timezone__limited',
        'timezone__browser',
    ])->and(app(CountryFieldPlayground::class)->defaultState())->toHaveKeys([
        'country__basic',
        'country__empty',
        'country__limited',
        'country__browser_locale',
    ])->and(app(TagsFieldPlayground::class)->defaultState())->toHaveKeys([
        'tags_field__basic',
        'tags_field__suggestions',
        'tags_field__reorderable',
    ])->and(app(CurrencyFieldPlayground::class)->defaultState())->toHaveKeys([
        'currency__pln',
        'currency__eur_usd',
        'currency__soft_multi',
    ])->and(app(UserSelectPlayground::class)->defaultState())->toHaveKeys([
        'user_select__single',
        'user_select__multiple',
        'user_select__members',
    ]);
});
