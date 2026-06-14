<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexChecklist;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRadiolist;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Schemas\Components\StateCasts\OptionsArrayStateCast;

it('uses gravity ui lock icon by default', function () {
    expect(FlexChecklist::make('permissions')->getLockIcon())->toBe(GravityIcon::Lock)
        ->and(FlexRadiolist::make('delivery')->getLockIcon())->toBe(GravityIcon::Lock);
});

it('exposes flex checklist configuration via fluent api', function () {
    $field = FlexChecklist::make('permissions')
        ->options([
            'documents' => 'Documents',
            'budget' => 'Budget.xlsx',
        ])
        ->icons([
            'documents' => 'heroicon-o-folder',
            'budget' => 'heroicon-o-table-cells',
        ])
        ->descriptions([
            'documents' => 'Shared project folder',
        ])
        ->disabledOptions(['archived'])
        ->minSelections(1)
        ->maxSelections(3)
        ->size('lg');

    $options = $field->getNormalizedOptions();

    expect($options['documents']['label'])->toBe('Documents')
        ->and($options['documents']['icon'])->toBe('heroicon-o-folder')
        ->and($options['documents']['description'])->toBe('Shared project folder')
        ->and($field->getMinSelections())->toBe(1)
        ->and($field->getMaxSelections())->toBe(3)
        ->and($field->getSize())->toBe('lg');
});

it('merges rich option arrays with icons and descriptions helpers', function () {
    $field = FlexChecklist::make('permissions')
        ->options([
            'reports' => [
                'label' => 'Reports',
                'icon' => 'heroicon-o-document-text',
                'description' => 'Inline description',
            ],
        ])
        ->icons([
            'reports' => 'heroicon-o-folder',
        ])
        ->descriptions([
            'reports' => 'Helper description',
        ]);

    $option = $field->getNormalizedOptions()['reports'];

    expect($option['label'])->toBe('Reports')
        ->and($option['icon'])->toBe('heroicon-o-document-text')
        ->and($option['description'])->toBe('Inline description');
});

it('marks disabled options from disabledOptions helper', function () {
    $field = FlexChecklist::make('permissions')
        ->options([
            'documents' => 'Documents',
            'archived' => 'Archived',
        ])
        ->disabledOptions(['archived']);

    $options = $field->getNormalizedOptions();

    expect($options['documents']['disabled'])->toBeFalse()
        ->and($options['archived']['disabled'])->toBeTrue()
        ->and($field->isOptionDisabled('archived'))->toBeTrue();
});

it('casts state to options array', function () {
    $field = FlexChecklist::make('permissions');

    $casts = collect($field->getDefaultStateCasts());

    expect($casts->contains(fn ($cast): bool => $cast instanceof OptionsArrayStateCast))->toBeTrue();
});

it('includes array validation rule', function () {
    $field = FlexChecklist::make('permissions')
        ->options(['a' => 'A', 'b' => 'B']);

    expect($field->getValidationRules())->toContain('array');
});

it('validates minimum selection count', function () {
    $field = FlexChecklist::make('permissions')
        ->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])
        ->minSelections(2);

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('permissions', ['a'], function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.flex_checklist.min', ['count' => 2]));
});

it('validates maximum selection count', function () {
    $field = FlexChecklist::make('permissions')
        ->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])
        ->maxSelections(2);

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('permissions', ['a', 'b', 'c'], function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.flex_checklist.max', ['count' => 2]));
});

it('validates exact selection count', function () {
    $field = FlexChecklist::make('permissions')
        ->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])
        ->exactSelections(2);

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('permissions', ['a'], function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.flex_checklist.exact', ['count' => 2]));
});

it('requires at least one selection when field is required', function () {
    $field = FlexChecklist::make('permissions')
        ->options(['a' => 'A', 'b' => 'B'])
        ->required();

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('permissions', [], function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.flex_checklist.min', ['count' => 1]));
});

it('rejects invalid option values', function () {
    $field = FlexChecklist::make('permissions')
        ->options(['a' => 'A', 'b' => 'B']);

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('permissions', ['a', 'invalid'], function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.flex_checklist.invalid_option'));
});

it('supports sm md and lg sizes', function () {
    expect(FlexChecklist::make('permissions')->size('sm')->getSize())->toBe('sm')
        ->and(FlexChecklist::make('permissions')->size('md')->getSize())->toBe('md')
        ->and(FlexChecklist::make('permissions')->size('lg')->getSize())->toBe('lg');
});

it('exposes checklist size css variables per size', function () {
    $small = FlexChecklist::make('permissions')->size('sm')->getChecklistSizeStyles();
    $large = FlexChecklist::make('permissions')->size('lg')->getChecklistSizeStyles();

    expect($small['--fff-flex-checklist-label-size'])->toBe('0.8125rem')
        ->and($large['--fff-flex-checklist-label-size'])->toBe('1rem')
        ->and($small['--fff-flex-checklist-indicator-size'])->toBe('0.875rem')
        ->and($large['--fff-flex-checklist-indicator-size'])->toBe('1.25rem');
});

it('defaults to primary color wrapper classes', function () {
    $field = FlexChecklist::make('permissions');

    expect($field->getColor())->toBe('primary')
        ->and($field->getWrapperClasses())->toBe([
            'fff-flex-checklist',
            'fff-flex-checklist--md',
            'fi-color-primary',
        ]);
});

it('allows options without icons', function () {
    $field = FlexChecklist::make('permissions')
        ->options([
            'documents' => 'Documents',
            'budget' => 'Budget.xlsx',
        ]);

    $options = $field->getNormalizedOptions();

    expect($options['documents']['icon'])->toBeNull()
        ->and($options['budget']['icon'])->toBeNull();
});

it('accepts desc as an inline option alias for description', function () {
    $field = FlexChecklist::make('permissions')
        ->options([
            'documents' => [
                'label' => 'Documents',
                'desc' => 'Updated 2 days ago',
            ],
        ]);

    expect($field->getNormalizedOptions()['documents']['description'])->toBe('Updated 2 days ago');
});
