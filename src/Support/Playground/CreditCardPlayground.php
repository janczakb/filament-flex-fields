<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CreditCardField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class CreditCardPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'credit_card__basic' => [
                'number' => '4242424242424242',
                'name' => 'Jan Kowalski',
                'expiry' => '12/28',
                'cvv' => '123',
            ],
            'credit_card__mastercard' => [
                'number' => '5555555555554444',
                'name' => 'Anna Nowak',
                'expiry' => '08/27',
                'cvv' => '456',
            ],
            'credit_card__ocean' => [
                'number' => '4242424242424242',
                'name' => 'Preview User',
                'expiry' => '03/29',
                'cvv' => '',
            ],
            'credit_card__sunset' => [
                'number' => '5555555555554444',
                'name' => 'Preview User',
                'expiry' => '11/26',
                'cvv' => '',
            ],
            'credit_card__slate' => [
                'number' => '',
                'name' => '',
                'expiry' => '',
                'cvv' => '',
            ],
            'credit_card__sm' => [
                'number' => '4242424242424242',
                'name' => 'Small Card',
                'expiry' => '01/30',
                'cvv' => '999',
            ],
            'credit_card__secondary' => [
                'number' => '4242424242424242',
                'name' => 'Secondary inputs',
                'expiry' => '04/29',
                'cvv' => '789',
            ],
            'credit_card__lg' => [
                'number' => '5555555555554444',
                'name' => 'Large Card',
                'expiry' => '06/31',
                'cvv' => '321',
            ],
            'credit_card__disabled' => [
                'number' => '4242424242424242',
                'name' => 'Disabled',
                'expiry' => '09/28',
                'cvv' => '111',
            ],
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Credit card')
                ->description('Live 3D card preview with Visa/Mastercard detection, front/back flip and sm/md/lg sizing.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    CreditCardField::make('credit_card__basic')
                        ->label('Payment card')
                        ->helperText('Focus CVV to flip to the back automatically, or use the flip button.')
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            CreditCardField::make('credit_card__mastercard')
                                ->label('Mastercard sample')
                                ->variant('midnight'),
                            CreditCardField::make('credit_card__ocean')
                                ->label('Ocean variant')
                                ->variant('ocean'),
                            CreditCardField::make('credit_card__sunset')
                                ->label('Sunset variant')
                                ->variant('sunset'),
                            CreditCardField::make('credit_card__slate')
                                ->label('Slate variant')
                                ->variant('slate'),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            CreditCardField::make('credit_card__sm')
                                ->label('Small')
                                ->size('sm'),
                            CreditCardField::make('credit_card__secondary')
                                ->label('Secondary inputs')
                                ->inputVariant('secondary'),
                            CreditCardField::make('credit_card__lg')
                                ->label('Large')
                                ->size('lg'),
                            CreditCardField::make('credit_card__disabled')
                                ->label('Disabled')
                                ->disabled(),
                        ]),
                ]),
        ];
    }
}
