<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieTagsField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TagsField;

it('exposes flex spatie tags field class', function () {
    expect(class_exists(FlexSpatieTagsField::class))->toBeTrue()
        ->and(is_subclass_of(FlexSpatieTagsField::class, TagsField::class))->toBeTrue()
        ->and((new FlexSpatieTagsField('topics'))->getView())->toBe('filament-flex-fields::forms.components.tags-field');
});

it('defaults to allowing any spatie tag type', function () {
    $field = FlexSpatieTagsField::make('topics');

    expect($field->allowsAnySpatieTagType())->toBeTrue()
        ->and($field->getSpatieTagType())->toBeNull();
});

it('scopes spatie tag type when configured', function () {
    $field = FlexSpatieTagsField::make('topics')->type('skills');

    expect($field->allowsAnySpatieTagType())->toBeFalse()
        ->and($field->getSpatieTagType())->toBe('skills');
});

it('enables live tag search by default instead of loading all suggestions', function () {
    $field = FlexSpatieTagsField::make('topics');

    expect($field->shouldSearchSuggestions())->toBeTrue()
        ->and($field->getSuggestions())->toBe([])
        ->and($field->getSuggestionsForJs())->toBe([]);
});
