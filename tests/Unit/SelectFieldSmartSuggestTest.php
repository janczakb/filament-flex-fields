<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\Select\RelationshipSearchAdapter;

it('exposes smart suggest configuration for js', function (): void {
    $field = SelectField::make('status')
        ->recentOptions(['open', 'closed'])
        ->suggestedOptions(['pending'])
        ->allowCreateOption()
        ->entityMentions(trigger: '@');

    expect($field->getSmartSuggestConfigForJs())->toBe([
        'enabled' => true,
        'recent' => ['open', 'closed'],
        'suggested' => ['pending'],
        'allowCreate' => true,
        'createLabel' => __('filament-flex-fields::default.select_field.smart_suggest.create'),
        'entityMentions' => true,
        'mentionTrigger' => '@',
    ]);
});

it('rejects unknown values when allowCreateOption is off and options are static', function (): void {
    $field = SelectField::make('status')
        ->label('Status')
        ->options([
            'draft' => 'Draft',
            'published' => 'Published',
        ]);

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    expect($rule)->toBeInstanceOf(Closure::class)
        ->and($field->shouldEnforceStaticOptionKeys())->toBeTrue();

    $failed = null;
    $rule('status', 'not-an-option', function (string $message) use (&$failed): void {
        $failed = $message;
    });

    expect($failed)->toBe(__('validation.in', ['attribute' => 'Status']));
});

it('accepts known values when allowCreateOption is off and options are static', function (): void {
    $field = SelectField::make('status')
        ->label('Status')
        ->options([
            'draft' => 'Draft',
            'published' => 'Published',
        ]);

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $failed = null;
    $rule('status', 'draft', function (string $message) use (&$failed): void {
        $failed = $message;
    });

    expect($failed)->toBeNull();
});

it('does not block created string keys when allowCreateOption is on', function (): void {
    $field = SelectField::make('status')
        ->label('Status')
        ->options([
            'draft' => 'Draft',
            'published' => 'Published',
        ])
        ->allowCreateOption();

    expect($field->shouldEnforceStaticOptionKeys())->toBeFalse()
        ->and($field->getInValidationRuleValues())->toBeNull();

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    expect($rule)->toBeInstanceOf(Closure::class);

    $failed = null;
    $rule('status', 'brand-new-label', function (string $message) use (&$failed): void {
        $failed = $message;
    });

    expect($failed)->toBeNull();
});

it('skips static option key enforcement for async search fields', function (): void {
    $field = SelectField::make('user_id')
        ->getSearchResultsUsing(fn (string $search): array => [])
        ->getOptionLabelUsing(fn ($value): ?string => null);

    expect($field->shouldEnforceStaticOptionKeys())->toBeFalse();
});

it('documents relationship search adapter contract', function (): void {
    $contract = RelationshipSearchAdapter::jsContract();

    expect($contract)->toHaveKeys(['fetch', 'cancel', 'debounceMs', 'warnLargeResultCount']);
});

it('ships allowCreateOption demos in the select-field playground hub', function (): void {
    $source = file_get_contents(__DIR__.'/../../src/Support/Playground/SelectPlayground.php');

    expect($source)
        ->toContain('allowCreateOption()')
        ->toContain('select__create_single')
        ->toContain('Smart suggest · create option');
});
