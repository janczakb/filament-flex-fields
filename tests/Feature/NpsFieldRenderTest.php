<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NpsField;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableNpsForm;
use Livewire\Livewire;

beforeEach(function (): void {
    TestableNpsForm::$formSchema = [];
});

it('renders nps field variants with lazy stylesheet hooks', function (): void {
    TestableNpsForm::$formSchema = [
        NpsField::make('score')->label('Score')->variant('pills'),
        NpsField::make('mood')->label('Mood')->variant('emojis')->options([
            0 => 'Awful',
            1 => 'Poor',
            2 => 'Neutral',
            3 => 'Good',
            4 => 'Excellent',
        ]),
    ];

    $html = Livewire::test(TestableNpsForm::class)->html(false);

    expect($html)
        ->toContain('fff-nps-field')
        ->toContain('fff-nps-field--variant-pills')
        ->toContain('fff-nps-field--variant-emojis')
        ->toContain('data-fff-asset-batch')
        ->toContain('data-fff-asset-consumer="nps-field"')
        ->toContain('npsFieldFormComponent({');
});
