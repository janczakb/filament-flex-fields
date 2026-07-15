<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CalculatorField;
use Bjanczak\FilamentFlexFields\Support\CalculatorPanelMount;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableCalculatorForm;
use Livewire\Livewire;

beforeEach(function (): void {
    TestableCalculatorForm::$formSchema = [];
    CalculatorPanelMount::reset();
});

it('renders calculator field shell and shared panel mount once', function (): void {
    TestableCalculatorForm::$formSchema = [
        CalculatorField::make('weight')->label('Weight'),
        CalculatorField::make('quantity')->label('Quantity'),
    ];

    $html = Livewire::test(TestableCalculatorForm::class)->html(false);

    expect($html)
        ->toContain('fff-calculator-field')
        ->toContain('calculatorFieldFormComponent({')
        ->toContain('fff-calculator-field__trigger')
        ->toContain('data-fff-calculator-panel-host')
        ->toContain('panelLabels')
        ->toContain('x-teleport="body"');

    expect(substr_count($html, 'data-fff-calculator-panel-host'))->toBe(1);
});
