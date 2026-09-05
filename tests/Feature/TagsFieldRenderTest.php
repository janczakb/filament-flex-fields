<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TagsField;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableSignatureForm;
use Livewire\Livewire;

beforeEach(function (): void {
    TestableSignatureForm::$formSchema = [];
});

it('renders tags field with select-style suggestions dropdown shell', function (): void {
    TestableSignatureForm::$formSchema = [
        TagsField::make('tags')
            ->suggestions(['laravel', 'filament', 'livewire'])
            ->default(['laravel']),
    ];

    $html = Livewire::test(TestableSignatureForm::class)->html(false);

    expect($html)
        ->toContain('fff-tags-field-input-mount')
        ->toContain('fff-tags-field-input-ssr')
        ->toContain('tagsInputMount')
        ->toContain('onSuggestionsClickOutside')
        ->toContain('tagsFieldFormComponent({')
        ->toContain('fff-select-dropdown-panel')
        ->toContain('fff-select-dropdown-panel--layout-plain')
        ->toContain('fi-select-input-options-ctn')
        ->toContain('fi-select-input-option')
        ->toContain('modulepreload')
        ->not->toContain('<datalist');
});

it('renders initial tags in ssr shell to prevent layout shift', function (): void {
    TestableSignatureForm::$formSchema = [
        TagsField::make('tags')
            ->tagSuffix('%')
            ->showTagCount()
            ->maxTags(5),
    ];

    $html = Livewire::test(TestableSignatureForm::class)
        ->set('data.tags', ['alpha', 'beta'])
        ->html(false);

    expect($html)
        ->toContain('fff-tags-field-input-ssr')
        ->toContain('fff-tags-field__tags-ssr')
        ->toContain('alpha%')
        ->toContain('beta%')
        ->toContain('2/5');
});

it('renders server search labels for select-style tag search', function (): void {
    TestableSignatureForm::$formSchema = [
        TagsField::make('tags')
            ->getSearchResultsUsing(static fn (string $search): array => [$search])
            ->minSearchLength(2),
    ];

    $html = Livewire::test(TestableSignatureForm::class)->html(false);

    expect($html)
        ->toContain('fff-select-dropdown-loading')
        ->toContain('fff-select-dropdown-empty')
        ->toContain(__('filament-flex-fields::default.tags.suggestions.min_chars', ['min' => 2]));
});

it('renders sortable handle on reorderable tags', function (): void {
    TestableSignatureForm::$formSchema = [
        TagsField::make('tags')
            ->reorderable()
            ->default(['alpha', 'beta']),
    ];

    $html = Livewire::test(TestableSignatureForm::class)->html(false);

    expect($html)
        ->toContain('x-sortable')
        ->toContain('x-sortable-handle')
        ->toContain('x-bind:x-sortable-item="index"')
        ->toContain('reorderTags($event)');
});
