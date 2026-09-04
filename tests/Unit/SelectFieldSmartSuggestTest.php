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
