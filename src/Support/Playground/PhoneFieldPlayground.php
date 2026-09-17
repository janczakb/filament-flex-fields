<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class PhoneFieldPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'phone__basic' => [
                'country' => 'PL',
                'national' => '512345678',
                'e164' => '+48512345678',
            ],
            'phone__us' => [
                'country' => 'US',
                'national' => '2345678901',
                'e164' => '+12345678901',
            ],
            'phone__limited' => [
                'country' => 'PL',
                'national' => '512345678',
                'e164' => '+48512345678',
            ],
            'phone__mobile_only' => [
                'country' => 'PL',
                'national' => '512345678',
                'e164' => '+48512345678',
            ],
            'phone__sm' => [
                'country' => 'DE',
                'national' => '1512345678',
                'e164' => '+491512345678',
            ],
            'phone__secondary' => [
                'country' => 'PL',
                'national' => '512345678',
                'e164' => '+48512345678',
            ],
            'phone__lg' => [
                'country' => 'GB',
                'national' => '7911123456',
                'e164' => '+447911123456',
            ],
            'phone__disabled' => [
                'country' => 'PL',
                'national' => '512345678',
                'e164' => '+48512345678',
            ],
            'phone__no_prefix' => [
                'country' => 'FR',
                'national' => '612345678',
                'e164' => '+33612345678',
            ],
            'phone__invalid' => [
                'country' => 'PL',
                'national' => '123',
                'e164' => '+48123',
            ],
            'phone__browser_locale' => [
                'country' => 'PL',
                'national' => '',
                'e164' => '',
            ],
            'phone__locale_pl' => [
                'country' => 'PL',
                'national' => '512345678',
                'e164' => '+48512345678',
            ],
            'phone__locale_en' => [
                'country' => 'PL',
                'national' => '512345678',
                'e164' => '+48512345678',
            ],
            'phone__fixed_line_only' => [
                'country' => 'PL',
                'national' => '123456789',
                'e164' => '+48123456789',
            ],
            'phone__except_countries' => [
                'country' => 'PL',
                'national' => '512345678',
                'e164' => '+48512345678',
            ],
            'phone__non_searchable' => [
                'country' => 'PL',
                'national' => '512345678',
                'e164' => '+48512345678',
            ],
            'phone__read_only' => [
                'country' => 'PL',
                'national' => '512345678',
                'e164' => '+48512345678',
            ],
            'phone__digits_national' => [
                'country' => 'PL',
                'national' => '512345678',
                'e164' => '+48512345678',
            ],
            'phone__validate_region' => [
                'country' => 'PL',
                'national' => '512345678',
                'e164' => '+48512345678',
            ],
            'phone__enriched' => [
                'country' => 'PL',
                'national' => '512345678',
                'e164' => '+48512345678',
            ],
            'phone__allow_types_voip' => [
                'country' => 'US',
                'national' => '',
                'e164' => '',
            ],
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Phone field')
                ->description('International phone input with circle flags, searchable country picker, libphonenumber validation and sm / md / lg sizing.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    PhoneField::make('phone__basic')
                        ->label('Phone number')
                        ->defaultCountry('PL')
                        ->helperText('Validates per country via libphonenumber — stores country, national digits and E.164.')
                        ->required()
                        ->columnSpanFull(),
                    PhoneField::make('phone__invalid')
                        ->label('Invalid number demo')
                        ->defaultCountry('PL')
                        ->helperText('Pre-filled invalid PL number — click Validate in the header to see the error state.')
                        ->required()
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            PhoneField::make('phone__us')
                                ->label('United States')
                                ->defaultCountry('US'),
                            PhoneField::make('phone__limited')
                                ->label('Limited countries')
                                ->countries(['PL', 'US', 'DE', 'GB', 'FR'])
                                ->defaultCountry('PL')
                                ->helperText('Only PL, US, DE, GB and FR are selectable.'),
                            PhoneField::make('phone__browser_locale')
                                ->label('Browser locale')
                                ->browserLocaleDefault()
                                ->browserLocaleSortFirst()
                                ->helperText('Defaults country from Accept-Language and puts it first in the dropdown.'),
                            PhoneField::make('phone__locale_pl')
                                ->label('Polish country names')
                                ->countries(['PL', 'US', 'DE'])
                                ->locale('pl')
                                ->helperText('Dropdown labels via locale(pl): Polska.'),
                            PhoneField::make('phone__locale_en')
                                ->label('English country names')
                                ->countries(['PL', 'US', 'DE'])
                                ->locale('en')
                                ->helperText('Dropdown labels via locale(en): Poland.'),
                            PhoneField::make('phone__mobile_only')
                                ->label('Mobile only')
                                ->defaultCountry('PL')
                                ->mobileOnly()
                                ->helperText('Validates mobile numbers only.'),
                            PhoneField::make('phone__fixed_line_only')
                                ->label('Fixed line only')
                                ->defaultCountry('PL')
                                ->fixedLineOnly()
                                ->helperText('Validates fixed-line numbers only.'),
                            PhoneField::make('phone__except_countries')
                                ->label('Except countries')
                                ->defaultCountry('PL')
                                ->exceptCountries(['RU', 'BY', 'CN'])
                                ->helperText('All regions except the excluded list.'),
                            PhoneField::make('phone__non_searchable')
                                ->label('Non-searchable picker')
                                ->defaultCountry('PL')
                                ->searchable(false)
                                ->helperText('Full country list without search (use Limited countries for a whitelist).'),
                            PhoneField::make('phone__read_only')
                                ->label('Read only')
                                ->defaultCountry('PL')
                                ->readOnly()
                                ->helperText('Value visible, not editable.'),
                            PhoneField::make('phone__digits_national')
                                ->label('Digits-only national')
                                ->defaultCountry('PL')
                                ->nationalDigitsOnly()
                                ->helperText('After normalize, national is digits only (opt-in; default stays NATIONAL).'),
                            PhoneField::make('phone__validate_region')
                                ->label('Validate for region')
                                ->defaultCountry('PL')
                                ->validateForRegion()
                                ->helperText('Uses isValidNumberForRegion against the selected country.'),
                            PhoneField::make('phone__enriched')
                                ->label('Enriched metadata')
                                ->defaultCountry('PL')
                                ->includeFormats(['international', 'rfc3966'])
                                ->includeMetadata(['carrier', 'geo', 'timezones', 'type'])
                                ->helperText('State also stores international, rfc3966, carrier, geo, timezones, type (PHP only).'),
                            PhoneField::make('phone__allow_types_voip')
                                ->label('Allow VOIP types')
                                ->defaultCountry('US')
                                ->allowTypes(['VOIP', 'MOBILE'])
                                ->helperText('allowTypes() — accepts VOIP or mobile (plus FIXED_LINE_OR_MOBILE unless strictTypes).'),
                            PhoneField::make('phone__no_prefix')
                                ->label('Without dial prefix')
                                ->defaultCountry('FR')
                                ->internationalPrefix(false),
                            PhoneField::make('phone__custom_icon')
                                ->label('Custom suffix icon')
                                ->defaultCountry('PL')
                                ->suffixIcon(GravityIcon::Handset)
                                ->helperText('Override with any icon set: gravityui-*, heroicon-o-*, ri-*, etc.'),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            PhoneField::make('phone__sm')
                                ->label('Small')
                                ->size('sm')
                                ->defaultCountry('DE'),
                            PhoneField::make('phone__secondary')
                                ->label('Secondary')
                                ->variant('secondary')
                                ->defaultCountry('PL'),
                            PhoneField::make('phone__soft')
                                ->label('Soft')
                                ->variant('soft')
                                ->defaultCountry('PL'),
                            PhoneField::make('phone__lg')
                                ->label('Large')
                                ->size('lg')
                                ->defaultCountry('GB'),
                            PhoneField::make('phone__disabled')
                                ->label('Disabled')
                                ->defaultCountry('PL')
                                ->disabled(),
                        ]),
                ]),
            PlaygroundCodeSnippet::make(<<<'PHP'
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;

// Basic — searchable country picker, E.164 validation
PhoneField::make('phone')
    ->label('Phone number')
    ->defaultCountry('PL')
    ->required();

// Limited countries + mobile only
PhoneField::make('phone')
    ->label('Limited countries')
    ->countries(['PL', 'US', 'DE', 'GB', 'FR'])
    ->defaultCountry('PL')
    ->mobileOnly();

// Browser locale default + sort-first
PhoneField::make('phone')
    ->label('Browser locale')
    ->browserLocaleDefault()
    ->browserLocaleSortFirst();

// Localized country names
PhoneField::make('phone')
    ->countries(['PL', 'US', 'DE'])
    ->locale('pl');

// Fixed line only / except countries / non-searchable / read-only
PhoneField::make('phone')->defaultCountry('PL')->fixedLineOnly();
PhoneField::make('phone')->defaultCountry('PL')->exceptCountries(['RU', 'BY', 'CN']);
PhoneField::make('phone')->defaultCountry('PL')->searchable(false);
PhoneField::make('phone')->defaultCountry('PL')->readOnly();

// Opt-in storage / validation (P2)
PhoneField::make('phone')->defaultCountry('PL')->nationalDigitsOnly();
PhoneField::make('phone')->defaultCountry('PL')->validateForRegion();
PhoneField::make('phone')
    ->defaultCountry('PL')
    ->includeFormats(['international', 'rfc3966'])
    ->includeMetadata(['carrier', 'geo', 'timezones', 'type']);
PhoneField::make('phone')
    ->defaultCountry('US')
    ->allowTypes(['VOIP', 'MOBILE']);

// Presentation
PhoneField::make('phone')->defaultCountry('FR')->internationalPrefix(false);
PhoneField::make('phone')->defaultCountry('PL')->suffixIcon(GravityIcon::Handset);
PhoneField::make('phone')->size('sm')->defaultCountry('DE');
PhoneField::make('phone')->variant('secondary')->defaultCountry('PL');
PhoneField::make('phone')->variant('soft')->defaultCountry('PL');
PhoneField::make('phone')->size('lg')->defaultCountry('GB');
PHP, filename: 'phone-field-usage.php'),
        ];
    }
}
