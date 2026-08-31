<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;

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
    $contract = Bjanczak\FilamentFlexFields\Support\Select\RelationshipSearchAdapter::jsContract();

    expect($contract)->toHaveKeys(['fetch', 'cancel', 'debounceMs', 'warnLargeResultCount']);
});
