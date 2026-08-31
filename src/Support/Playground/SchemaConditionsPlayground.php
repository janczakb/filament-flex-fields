<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class SchemaConditionsPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'schema_conditions__account_type' => 'person',
            'schema_conditions__company_name' => '',
            'schema_conditions__vat_id' => '',
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Schema conditions')
                ->description('JSON visibleWhen/requiredWhen compile to these Filament closures via FormBuilder — switch account type to see company fields appear and VAT become required.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    SelectField::make('schema_conditions__account_type')
                        ->label('Account type')
                        ->options([
                            'person' => 'Person',
                            'company' => 'Company',
                        ])
                        ->live(),
                    FlexTextInput::make('schema_conditions__company_name')
                        ->label('Company name')
                        ->visible(fn (Get $get): bool => $get('schema_conditions__account_type') === 'company'),
                    FlexTextInput::make('schema_conditions__vat_id')
                        ->label('VAT ID')
                        ->required(fn (Get $get): bool => $get('schema_conditions__account_type') === 'company'),
                    PlaygroundCodeSnippet::make(<<<'PHP'
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Filament\Schemas\Components\Utilities\Get;

SelectField::make('account_type')
    ->label('Account type')
    ->options([
        'person' => 'Person',
        'company' => 'Company',
    ])
    ->live(),

FlexTextInput::make('company_name')
    ->label('Company name')
    ->visible(fn (Get $get): bool => $get('account_type') === 'company'),

FlexTextInput::make('vat_id')
    ->label('VAT ID')
    ->required(fn (Get $get): bool => $get('account_type') === 'company'),
PHP, filename: 'schema-conditions.php'),
                ]),
        ];
    }
}
