<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Support\FlexFieldFormBuilder;

it('applies hidden label default and helper text from field definition', function (): void {
    $component = app(FlexFieldFormBuilder::class)->makeComponent(
        new FlexFieldDefinition(
            slug: 'first_name',
            label: 'First name',
            type: FieldType::FlexTextInput,
            defaultValue: 'John',
            helpText: 'Shown under the input',
            hiddenLabel: true,
        ),
        '',
    );

    expect($component)->toBeInstanceOf(FlexTextInput::class)
        ->and($component->isLabelHidden())->toBeTrue()
        ->and($component->getDefaultState())->toBe('John')
        ->and($component->getLabel())->toBe('First name');
});
