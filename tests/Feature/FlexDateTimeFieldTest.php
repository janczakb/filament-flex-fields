<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateRangeField;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableDateTimeForm;
use Livewire\Livewire;

beforeEach(function (): void {
    TestableDateTimeForm::$formSchema = [];
});

it('fills and asserts a single date field through livewire', function (): void {
    TestableDateTimeForm::$formSchema = [
        FlexDateField::make('starts_on')->withRecommendedDefaults(),
    ];

    Livewire::test(TestableDateTimeForm::class)
        ->set('data.starts_on', '2026-06-15')
        ->assertSet('data.starts_on', '2026-06-15');
});

it('fills and asserts a date range field through livewire', function (): void {
    TestableDateTimeForm::$formSchema = [
        FlexDateRangeField::make('booking_range')->withRecommendedDefaults(),
    ];

    Livewire::test(TestableDateTimeForm::class)
        ->set('data.booking_range', [
            'start' => '2026-06-10',
            'end' => '2026-06-14',
        ])
        ->assertSet('data.booking_range.start', '2026-06-10')
        ->assertSet('data.booking_range.end', '2026-06-14');
});
