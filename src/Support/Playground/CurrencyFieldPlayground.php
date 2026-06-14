<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class CurrencyFieldPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'currency__pln' => 6_666_660,
            'currency__eur_usd' => [
                'amount' => 125_050,
                'currency' => 'EUR',
            ],
            'currency__empty' => null,
            'currency__sm' => 9_999,
            'currency__soft' => [
                'amount' => 12_500,
                'currency' => 'PLN',
            ],
            'currency__soft_multi' => [
                'amount' => 125_050,
                'currency' => 'EUR',
            ],
            'currency__lg' => 1_000_000,
            'currency__limited' => [
                'amount' => 50_000,
                'currency' => 'USD',
            ],
            'currency__negative' => -1_250,
            'currency__disabled' => 42_000,
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Currency field')
                ->description('Revolut-style currency input with minor-unit storage, locale-aware formatting, digit animations and optional currency picker.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    CurrencyField::make('currency__pln')
                        ->label('Amount (PLN)')
                        ->currency('PLN')
                        ->locale('pl_PL')
                        ->helperText('Stores minor units — 6 666 660 = 66 666,60 zł.')
                        ->required()
                        ->columnSpanFull(),
                    CurrencyField::make('currency__eur_usd')
                        ->label('Multi-currency')
                        ->currencies(['EUR', 'USD', 'GBP', 'PLN'])
                        ->currency('EUR')
                        ->helperText('Composite state: amount (minor units) + currency code.')
                        ->columnSpanFull(),
                    CurrencyField::make('currency__soft_multi')
                        ->label('Soft with currency picker')
                        ->variant('soft')
                        ->currencies(['EUR', 'USD', 'GBP', 'PLN'])
                        ->currency('EUR')
                        ->helperText('Soft shell with searchable currency dropdown on the left.')
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            CurrencyField::make('currency__empty')
                                ->label('Empty')
                                ->currency('PLN')
                                ->locale('pl_PL'),
                            CurrencyField::make('currency__sm')
                                ->label('Small')
                                ->size('sm')
                                ->currency('EUR'),
                            CurrencyField::make('currency__soft')
                                ->label('Soft')
                                ->variant('soft')
                                ->currencies(['PLN', 'EUR', 'USD'])
                                ->currency('PLN')
                                ->locale('pl_PL'),
                            CurrencyField::make('currency__lg')
                                ->label('Large')
                                ->size('lg')
                                ->currency('USD'),
                            CurrencyField::make('currency__limited')
                                ->label('Limited currencies')
                                ->currencies(['USD', 'GBP'])
                                ->currency('USD')
                                ->min(0)
                                ->max(99999.99),
                            CurrencyField::make('currency__negative')
                                ->label('Allow negative')
                                ->currency('PLN')
                                ->allowNegative()
                                ->min(-1000)
                                ->max(1000),
                            CurrencyField::make('currency__disabled')
                                ->label('Disabled')
                                ->currency('GBP')
                                ->disabled(),
                        ]),
                ]),
        ];
    }
}
