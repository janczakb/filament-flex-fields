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
                            PhoneField::make('phone__mobile_only')
                                ->label('Mobile only')
                                ->defaultCountry('PL')
                                ->mobileOnly()
                                ->helperText('Validates mobile numbers only.'),
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
        ];
    }
}
