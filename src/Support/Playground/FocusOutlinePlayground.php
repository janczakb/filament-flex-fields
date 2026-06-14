<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CreditCardField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class FocusOutlinePlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'focus_outline__text' => '',
            'focus_outline__textarea' => '',
            'focus_outline__select' => 'published',
            'focus_outline__phone' => [
                'country' => 'PL',
                'national' => '',
                'e164' => '',
            ],
            'focus_outline__credit_card' => [
                'number' => '',
                'name' => '',
                'expiry' => '',
                'cvv' => '',
            ],
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        $statusOptions = [
            'draft' => 'Draft',
            'reviewing' => 'Reviewing',
            'published' => 'Published',
        ];

        return [
            Section::make('Focus outline')
                ->description('Tab or click into each field — a blue ring appears around the shell. Enable with ->focusOutline() on Flex Text Input, Textarea, Select, Phone, and Credit Card (on by default for credit card).')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            FlexTextInput::make('focus_outline__text')
                                ->label('Flex Text Input')
                                ->focusOutline()
                                ->placeholder('Tab here to see the ring'),
                            SelectField::make('focus_outline__select')
                                ->label('Select')
                                ->options($statusOptions)
                                ->focusOutline(),
                            FlexTextareaField::make('focus_outline__textarea')
                                ->label('Flex Textarea')
                                ->focusOutline()
                                ->placeholder('Tab here to see the ring')
                                ->maxLength(240)
                                ->characterCounter(),
                            PhoneField::make('focus_outline__phone')
                                ->label('Phone')
                                ->focusOutline()
                                ->defaultCountry('PL'),
                        ]),
                    CreditCardField::make('focus_outline__credit_card')
                        ->label('Credit Card (focus outline on by default)')
                        ->focusOutline()
                        ->columnSpanFull(),
                ]),
        ];
    }
}
