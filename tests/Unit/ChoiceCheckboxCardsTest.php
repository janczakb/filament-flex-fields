<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCheckboxCards;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Schemas\Components\StateCasts\OptionsArrayStateCast;

it('exposes choice checkbox cards configuration via fluent api', function () {
    $field = ChoiceCheckboxCards::make('toppings')
        ->options([
            'cheese' => [
                'label' => 'Cheese',
                'description' => 'Extra mozzarella',
                'icon' => GravityIcon::ShieldCheck,
            ],
        ])
        ->layout('grid')
        ->gridColumns(['default' => 1, 'sm' => 3])
        ->indicator('checkbox')
        ->minSelections(1)
        ->maxSelections(3)
        ->variant('primary')
        ->ripple();

    expect($field->getLayout())->toBe('grid')
        ->and($field->getGridColumnConfig()['sm'])->toBe(3)
        ->and($field->getIndicator())->toBe('checkbox')
        ->and($field->getMinSelections())->toBe(1)
        ->and($field->getMaxSelections())->toBe(3)
        ->and($field->getVariant())->toBe('primary')
        ->and($field->isRippleEnabled())->toBeTrue()
        ->and($field->getNormalizedOptions()['cheese']['icon'])->toBe(GravityIcon::ShieldCheck);
});

it('accepts heroicon or any icon set in choice checkbox card options', function () {
    $field = ChoiceCheckboxCards::make('permissions')
        ->options([
            'analytics' => [
                'label' => 'Analytics',
                'icon' => 'heroicon-o-chart-bar-square',
            ],
        ]);

    expect($field->getNormalizedOptions()['analytics']['icon'])->toBe('heroicon-o-chart-bar-square');
});

it('resolves checkbox indicator automatically by layout', function () {
    $stack = ChoiceCheckboxCards::make('toppings')->layout('stack');
    $media = ChoiceCheckboxCards::make('payment')->layout('media');
    $featured = ChoiceCheckboxCards::make('workspace')->layout('featured');

    expect($stack->getIndicator())->toBe('checkbox')
        ->and($media->getIndicator())->toBe('none')
        ->and($featured->getIndicator())->toBe('check');
});

it('casts state to options array', function () {
    $field = ChoiceCheckboxCards::make('toppings');

    $casts = collect($field->getDefaultStateCasts());

    expect($casts->contains(fn ($cast): bool => $cast instanceof OptionsArrayStateCast))->toBeTrue();
});

it('includes array validation rule', function () {
    $field = ChoiceCheckboxCards::make('toppings')
        ->options(['a' => 'A', 'b' => 'B']);

    expect($field->getValidationRules())->toContain('array');
});

it('validates minimum selection count', function () {
    $field = ChoiceCheckboxCards::make('toppings')
        ->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])
        ->minSelections(2);

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('toppings', ['a'], function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.choice_checkbox_cards.min', ['count' => 2]));
});

it('validates maximum selection count', function () {
    $field = ChoiceCheckboxCards::make('toppings')
        ->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])
        ->maxSelections(2);

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('toppings', ['a', 'b', 'c'], function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.choice_checkbox_cards.max', ['count' => 2]));
});

it('validates exact selection count', function () {
    $field = ChoiceCheckboxCards::make('toppings')
        ->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])
        ->exactSelections(2);

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('toppings', ['a'], function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.choice_checkbox_cards.exact', ['count' => 2]));
});

it('requires at least one selection when field is required', function () {
    $field = ChoiceCheckboxCards::make('toppings')
        ->options(['a' => 'A', 'b' => 'B'])
        ->required();

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('toppings', [], function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.choice_checkbox_cards.min', ['count' => 1]));
});

it('rejects invalid option values', function () {
    $field = ChoiceCheckboxCards::make('toppings')
        ->options(['a' => 'A', 'b' => 'B']);

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('toppings', ['a', 'invalid'], function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.choice_checkbox_cards.invalid_option'));
});

it('supports sm md and lg sizes', function () {
    expect(ChoiceCheckboxCards::make('toppings')->size('sm')->getSize())->toBe('sm')
        ->and(ChoiceCheckboxCards::make('toppings')->size('md')->getSize())->toBe('md')
        ->and(ChoiceCheckboxCards::make('toppings')->size('lg')->getSize())->toBe('lg');
});

it('exposes choice card size css variables per size', function () {
    $small = ChoiceCheckboxCards::make('toppings')->size('sm')->getChoiceCardSizeStyles();
    $large = ChoiceCheckboxCards::make('toppings')->size('lg')->getChoiceCardSizeStyles();

    expect($small['--fff-choice-cards-p'])->toBe('0.625rem')
        ->and($large['--fff-choice-cards-p'])->toBe('1.5rem')
        ->and($small['--fff-choice-cards-label-size'])->toBe('0.8125rem')
        ->and($large['--fff-choice-cards-label-size'])->toBe('1.125rem');
});
