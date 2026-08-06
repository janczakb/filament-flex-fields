<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Concerns\HasFlexFields;
use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\Playground\NumberStepperPlayground;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableNumberStepperForm;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;

it('exposes number stepper configuration via fluent api', function () {
    $field = NumberStepper::make('max_visits')
        ->minValue(1)
        ->maxValue(500)
        ->step(5)
        ->nullable()
        ->nullLabel('No limit')
        ->prefix('$')
        ->suffix('visits')
        ->decimalPlaces(2)
        ->variant('primary')
        ->size(ControlSize::Lg)
        ->reversed()
        ->icons([
            'decrement' => 'heroicon-o-minus',
            'increment' => 'heroicon-o-plus',
        ])
        ->wheelAnimated();

    expect($field->getMinValue())->toBe(1)
        ->and($field->getMaxValue())->toBe(500)
        ->and($field->getStep())->toBe(5)
        ->and($field->isNullable())->toBeTrue()
        ->and($field->getNullLabel())->toBe('No limit')
        ->and($field->getDisplayPrefix())->toBe('$')
        ->and($field->getDisplaySuffix())->toBe('visits')
        ->and($field->getDecimalPlaces())->toBe(2)
        ->and($field->getVariant())->toBe('primary')
        ->and($field->getSize())->toBe('lg')
        ->and($field->isReversed())->toBeTrue()
        ->and($field->getDecrementIcon())->toBe('heroicon-o-minus')
        ->and($field->getIncrementIcon())->toBe('heroicon-o-plus')
        ->and($field->isWheelAnimated())->toBeTrue()
        ->and($field->isInteger())->toBeTrue()
        ->and($field->isNumeric())->toBeTrue();
});

it('defaults to gravity ui minus and plus icons when no custom icons are set', function () {
    $field = NumberStepper::make('count');

    expect($field->getDecrementIcon())->toBe(GravityIcon::Minus)
        ->and($field->getIncrementIcon())->toBe(GravityIcon::Plus)
        ->and($field->getDefaultDecrementIcon())->toBe(GravityIcon::Minus)
        ->and($field->getDefaultIncrementIcon())->toBe(GravityIcon::Plus);
});

it('allows overriding stepper icons with heroicon or any icon set', function () {
    $field = NumberStepper::make('count')->icons([
        'decrement' => Heroicon::OutlinedMinus,
        'increment' => Heroicon::OutlinedPlus,
    ]);

    expect($field->getDecrementIcon())->toBe(Heroicon::OutlinedMinus)
        ->and($field->getIncrementIcon())->toBe(Heroicon::OutlinedPlus);
});

it('reads default stepper icons from config', function () {
    config([
        'filament-flex-fields.ui.number_stepper_decrement_icon' => 'heroicon-o-minus',
        'filament-flex-fields.ui.number_stepper_increment_icon' => 'heroicon-o-plus',
    ]);

    expect(NumberStepper::make('count')->getDefaultDecrementIcon())->toBe('heroicon-o-minus')
        ->and(NumberStepper::make('count')->getDefaultIncrementIcon())->toBe('heroicon-o-plus');
});

it('computes a stable width anchor for nullable fields', function () {
    $field = NumberStepper::make('limit')
        ->nullable()
        ->nullLabel('No limit');

    expect($field->getWidthAnchorText())->toBe('No limit');
});

it('buckets one and two digit values into the same width anchor', function () {
    $field = NumberStepper::make('count')->maxValue(99);

    expect($field->formatBucketedDisplay(1))->toBe('88')
        ->and($field->formatBucketedDisplay(10))->toBe('88')
        ->and($field->getWidthAnchorText())->toBe('88');
});

it('keeps the width anchor at two digits until the value grows', function () {
    $field = NumberStepper::make('count')->maxValue(500);

    expect($field->formatBucketedDisplay(100))->toBe('888')
        ->and($field->getWidthAnchorMainText())->toBe('88');
});

it('includes prefix, decimals, and suffix in the width anchor', function () {
    $field = NumberStepper::make('price')
        ->prefix('$')
        ->suffix('%')
        ->decimalPlaces(2)
        ->maxValue(999);

    expect($field->getWidthAnchorText())->toBe('$88.88'."\u{00a0}".'%');
});

it('formats display values for server-side rendering', function () {
    $field = NumberStepper::make('price')
        ->prefix('$')
        ->suffix('%')
        ->decimalPlaces(2);

    expect($field->hasDisplayValue(null))->toBeFalse()
        ->and($field->formatDisplayValue(null))->toBeNull()
        ->and($field->formatDisplayValue(12))->toBe('$12.00'."\u{00a0}".'%')
        ->and($field->formatDisplayMain(12))->toBe('$12.00')
        ->and($field->getInitialSizerText(12))->toBe('$88.88'."\u{00a0}".'%');
});

it('stores and reads flex field values on models', function () {
    $model = new class extends Model
    {
        use HasFlexFields;

        protected $guarded = [];

        public $timestamps = false;
    };

    $model->initializeHasFlexFields();

    $model->setFlexFieldValue('bio', 'Hello world');

    expect($model->getFlexFieldValue('bio'))->toBe('Hello world')
        ->and($model->getFlexFieldValues())->toBe(['bio' => 'Hello world'])
        ->and($model->getCasts()['flex_field_values'])->toBe('array');
});

it('merges playground default state from dedicated variant classes', function () {
    $state = app(FlexFieldsPlaygroundBuilder::class)->defaultState();

    expect($state)->toHaveKeys([
        'number_stepper__sm',
        'number_stepper__custom_icons',
        'number_stepper__primary',
        'segment_control__ghost_reference',
    ]);
});

it('registers hero number stepper playground variants', function () {
    $state = (new NumberStepperPlayground)->defaultState();

    expect($state)->toHaveKeys([
        'number_stepper__currency',
        'number_stepper__reversed',
        'number_stepper__digit_overflow',
        'number_stepper__adults',
    ]);
});

it('entangles state non-live and passes the debounce ms to alpine when live(debounce:) is set', function () {
    // Regression (GitHub #40): Filament silently drops `debounce` for
    // `$entangle()`-bound fields, so every +/- click fired a Livewire
    // request immediately and lagged the page. The blade view must instead
    // entangle non-live and hand the debounce off to the Alpine component.
    TestableNumberStepperForm::$formSchema = [
        NumberStepper::make('count')->live(debounce: 500),
    ];

    $html = html_entity_decode(Livewire::test(TestableNumberStepperForm::class)->html());

    expect($html)
        ->toContain("entangle('data.count', false)")
        ->toContain('liveDebounce: 500');
});

it('keeps entangling state live with no client debounce when live() has no debounce', function () {
    TestableNumberStepperForm::$formSchema = [
        NumberStepper::make('count')->live(),
    ];

    $html = html_entity_decode(Livewire::test(TestableNumberStepperForm::class)->html());

    expect($html)
        ->toContain("entangle('data.count', true)")
        ->toContain('liveDebounce: null');
});

it('keeps entangling state non-live with no client debounce when the field is not live at all', function () {
    TestableNumberStepperForm::$formSchema = [
        NumberStepper::make('count'),
    ];

    $html = html_entity_decode(Livewire::test(TestableNumberStepperForm::class)->html());

    expect($html)
        ->toContain("entangle('data.count', false)")
        ->toContain('liveDebounce: null');
});

it('normalizes the live debounce delay into milliseconds for the alpine component', function () {
    expect(NumberStepper::make('count')->live(debounce: 500)->isLiveDebounced())->toBeTrue()
        ->and(NumberStepper::make('count')->live(debounce: 500)->getNormalizedLiveDebounce())->toBe(500)
        ->and(NumberStepper::make('count')->live(debounce: '1s')->getNormalizedLiveDebounce())->toBe(1000);
});

it('debounces the committed value in the alpine component instead of committing on every click', function () {
    $js = file_get_contents(__DIR__.'/../../resources/js/components/number-stepper.js');

    expect($js)
        ->toContain('commitDebounced()')
        ->toContain('clearTimeout(this.commitTimer)')
        ->toContain('this.$wire.set(this.statePath, this.state, true)');
});
