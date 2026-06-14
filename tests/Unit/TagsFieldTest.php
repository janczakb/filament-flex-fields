<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TagsField;
use Bjanczak\FilamentFlexFields\Support\FlexFieldFormBuilder;

it('exposes tags configuration via fluent api', function () {
    $field = TagsField::make('tags')
        ->label('Tags')
        ->size('lg')
        ->variant('primary')
        ->maxTags(5)
        ->suggestions(['laravel', 'filament'])
        ->suggestionsOnly()
        ->duplicateInsensitive()
        ->showTagCount()
        ->separator(',')
        ->tagSuffix('%')
        ->reorderable()
        ->color('danger')
        ->trim()
        ->nestedRecursiveRules(['min:2', 'max:32']);

    expect($field->getSize())->toBe('lg')
        ->and($field->getVariant())->toBe('primary')
        ->and($field->getMaxTags())->toBe(5)
        ->and($field->getSuggestions())->toBe(['laravel', 'filament'])
        ->and($field->isSuggestionsOnly())->toBeTrue()
        ->and($field->isDuplicateInsensitive())->toBeTrue()
        ->and($field->shouldShowTagCount())->toBeTrue()
        ->and($field->getSeparator())->toBe(',')
        ->and($field->getTagSuffix())->toBe('%')
        ->and($field->isReorderable())->toBeTrue()
        ->and($field->getColor())->toBe('danger')
        ->and($field->isTrimmed())->toBeTrue()
        ->and($field->getNestedRecursiveValidationRules())->toBe(['min:2', 'max:32']);
});

it('formats tag labels with prefix and suffix', function () {
    $field = TagsField::make('tags')
        ->tagPrefix('#')
        ->tagSuffix('!');

    expect($field->getTagDisplayLabel('news'))->toBe('#news!');
});

it('supports comma separated storage configuration', function () {
    $field = TagsField::make('tags')->separator(',');

    expect($field->getSeparator())->toBe(',');
});

it('maps tags field type through flex field form builder', function () {
    $component = app(FlexFieldFormBuilder::class)->makeComponent(
        new FlexFieldDefinition(
            slug: 'skills',
            label: 'Skills',
            type: FieldType::Tags,
            config: [
                'suggestions' => ['laravel', 'php'],
                'max_tags' => 4,
            ],
        ),
    );

    expect($component)->toBeInstanceOf(TagsField::class)
        ->and($component->getSuggestions())->toBe(['laravel', 'php'])
        ->and($component->getMaxTags())->toBe(4);
});

it('includes tags field wrapper classes', function () {
    $field = TagsField::make('tags')
        ->size('sm')
        ->color('success');

    expect($field->getWrapperClasses())->toContain('fff-tags-field')
        ->and($field->getWrapperClasses())->toContain('fff-tags-field--sm')
        ->and($field->getWrapperClasses())->toContain('fff-flex-text-input--sm')
        ->and($field->getWrapperClasses())->toContain('fff-flex-text-input--primary')
        ->and($field->getWrapperClasses())->toContain('fi-color-success');
});

it('rejects unsupported tags field variants', function () {
    TagsField::make('tags')->variant('ghost')->getVariant();
})->throws(InvalidArgumentException::class);

it('exposes live tag search configuration via fluent api', function () {
    $callback = static fn (string $search): array => ['laravel', 'php'];

    $field = TagsField::make('tags')
        ->getSearchResultsUsing($callback)
        ->minSearchLength(3);

    expect($field->shouldSearchSuggestions())->toBeTrue()
        ->and($field->getMinSearchLength())->toBe(3)
        ->and($field->getSuggestionsForJs())->toBe([]);
});

it('returns empty suggestions for js when live search is enabled', function () {
    $field = TagsField::make('tags')
        ->suggestions(['laravel', 'filament'])
        ->getSearchResultsUsing(static fn (string $search): array => [$search]);

    expect($field->getSuggestions())->toBe(['laravel', 'filament'])
        ->and($field->getSuggestionsForJs())->toBe([]);
});

it('searches tag suggestions using the configured callback', function () {
    $field = TagsField::make('tags')
        ->getSearchResultsUsing(static fn (string $search): array => match ($search) {
            'la' => ['laravel'],
            'ph' => ['php'],
            default => [],
        })
        ->minSearchLength(2);

    expect($field->searchTagSuggestions('l'))->toBe([])
        ->and($field->searchTagSuggestions('la'))->toBe(['laravel'])
        ->and($field->searchTagSuggestions('ph'))->toBe(['php']);
});

it('falls back to static suggestions when live search is not configured', function () {
    $field = TagsField::make('tags')->suggestions(['laravel', 'php']);

    expect($field->shouldSearchSuggestions())->toBeFalse()
        ->and($field->getSuggestionsForJs())->toBe(['laravel', 'php'])
        ->and($field->searchTagSuggestions('la'))->toBe(['laravel', 'php']);
});
