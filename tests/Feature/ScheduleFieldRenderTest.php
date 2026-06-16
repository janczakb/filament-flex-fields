<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ScheduleField;
use Bjanczak\FilamentFlexFields\Support\Schedule\ScheduleDays;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableScheduleForm;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function (): void {
    TestableScheduleForm::$formSchema = [];
});

it('renders schedule field shell and alpine configuration', function (): void {
    TestableScheduleForm::$formSchema = [
        ScheduleField::make('hours')
            ->timezone('UTC')
            ->required(),
    ];

    $html = Livewire::test(TestableScheduleForm::class)->html(false);

    expect($html)
        ->toContain('fff-schedule-field')
        ->toContain('scheduleFieldFormComponent({')
        ->toContain('flexTimeSegmentsSrc')
        ->toContain('dayValidationErrorMessage')
        ->toContain('aria-label')
        ->toContain('role="option"');
});

it('fails server validation when required schedule has no enabled days with slots', function (): void {
    TestableScheduleForm::$formSchema = [
        ScheduleField::make('hours')
            ->timezone(null)
            ->required(),
    ];

    $emptySchedule = [
        'days' => array_fill_keys(ScheduleDays::ALL, [
            'enabled' => false,
            'slots' => [],
        ]),
    ];

    $component = Livewire::test(TestableScheduleForm::class)
        ->set('data.hours', $emptySchedule);

    expect(fn () => $component->instance()->getSchema('form')->validate())
        ->toThrow(ValidationException::class);
});

it('passes server validation for a valid required schedule', function (): void {
    TestableScheduleForm::$formSchema = [
        ScheduleField::make('hours')
            ->timezone('UTC')
            ->required(),
    ];

    Livewire::test(TestableScheduleForm::class)
        ->set('data.hours', ScheduleField::defaultSchedule('UTC'))
        ->call('save')
        ->assertHasNoErrors();
});

it('fails server validation when enabled day slots overlap', function (): void {
    TestableScheduleForm::$formSchema = [
        ScheduleField::make('hours')->timezone('UTC'),
    ];

    $state = ScheduleField::defaultSchedule('UTC');
    $state['days']['mon']['slots'] = [
        ['from' => '09:00', 'to' => '12:00'],
        ['from' => '11:00', 'to' => '13:00'],
    ];

    $component = Livewire::test(TestableScheduleForm::class)
        ->set('data.hours', $state);

    expect(fn () => $component->instance()->getSchema('form')->validate())
        ->toThrow(ValidationException::class);
});
