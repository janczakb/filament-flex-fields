<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\IconPickerField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers\TextFieldTypeHandler;

it('builds icon picker fields from form builder definitions', function () {
    $handler = new TextFieldTypeHandler;
    $definition = new FlexFieldDefinition(
        slug: 'menu_icon',
        label: 'Menu icon',
        type: FieldType::IconPicker,
        config: [
            'sets' => ['heroicons'],
            'icons' => ['heroicon-o-star'],
            'layout' => 'list',
            'close_on_select' => false,
            'per_page' => 24,
        ],
    );

    $field = $handler->make($definition, 'menu_icon');

    expect($field)->toBeInstanceOf(IconPickerField::class)
        ->and($field->getConfiguredSets())->toBe(['heroicons'])
        ->and($field->getWhitelistedIcons())->toBe(['heroicon-o-star'])
        ->and($field->getSearchResultsLayout())->toBe('list')
        ->and($field->shouldCloseOnSelect())->toBeFalse()
        ->and($field->getPerPage())->toBe(24);
});
