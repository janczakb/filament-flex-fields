<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexColorPickerField;
use Bjanczak\FilamentFlexFields\Support\FlexFieldFormBuilder;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;

it('exposes flex color picker configuration api', function () {
    $field = FlexColorPickerField::make('accent')
        ->layout(FlexColorPickerField::LAYOUT_GRID)
        ->variant('secondary')
        ->rgba()
        ->alpha()
        ->eyedropper(false)
        ->gridColumns(8)
        ->gridRows(6)
        ->gridColors(['#FF0000', '#00FF00'])
        ->size('lg');

    expect($field->getLayout())->toBe(FlexColorPickerField::LAYOUT_GRID)
        ->and($field->getVariant())->toBe('secondary')
        ->and($field->getFormat())->toBe(FlexColorPickerField::FORMAT_RGBA)
        ->and($field->isAlphaEnabled())->toBeTrue()
        ->and($field->isEyedropperEnabled())->toBeFalse()
        ->and($field->getGridColumns())->toBe(8)
        ->and($field->getGridRows())->toBe(6)
        ->and($field->getGridColors())->toBe(['#FF0000', '#00FF00'])
        ->and($field->getSize())->toBe('lg');
});

it('defaults to advanced layout and hex format', function () {
    $field = FlexColorPickerField::make('accent');

    expect($field->getLayout())->toBe(FlexColorPickerField::LAYOUT_ADVANCED)
        ->and($field->getVariant())->toBe('primary')
        ->and($field->getFormat())->toBe(FlexColorPickerField::FORMAT_HEX)
        ->and($field->isAlphaEnabled())->toBeFalse()
        ->and($field->isEyedropperEnabled())->toBeTrue()
        ->and($field->getGridColumns())->toBe(17)
        ->and($field->getGridRows())->toBe(11)
        ->and($field->getGridColors())->toBeNull();
});

it('supports shorthand output format methods', function () {
    expect(FlexColorPickerField::make('a')->hex()->getFormat())->toBe(FlexColorPickerField::FORMAT_HEX)
        ->and(FlexColorPickerField::make('a')->rgb()->getFormat())->toBe(FlexColorPickerField::FORMAT_RGB)
        ->and(FlexColorPickerField::make('a')->hsl()->getFormat())->toBe(FlexColorPickerField::FORMAT_HSL)
        ->and(FlexColorPickerField::make('a')->rgba()->alpha()->getFormat())->toBe(FlexColorPickerField::FORMAT_RGBA);
});

it('validates supported color strings', function () {
    $field = FlexColorPickerField::make('accent');

    expect($field->isValidColorString('#6366F1'))->toBeTrue()
        ->and($field->isValidColorString('#fff'))->toBeTrue()
        ->and($field->isValidColorString('#6366F1AA'))->toBeTrue()
        ->and($field->isValidColorString('rgb(99, 102, 241)'))->toBeTrue()
        ->and($field->isValidColorString('rgba(99, 102, 241, 0.5)'))->toBeTrue()
        ->and($field->isValidColorString('hsl(262, 83%, 58%)'))->toBeTrue()
        ->and($field->isValidColorString('hsla(262, 83%, 58%, 0.25)'))->toBeTrue()
        ->and($field->isValidColorString('not-a-color'))->toBeFalse()
        ->and($field->isValidColorString('rgb(300, 0, 0)'))->toBeFalse();
});

it('rejects unsupported layout and format values', function () {
    FlexColorPickerField::make('accent')->layout('invalid')->getLayout();
})->throws(InvalidArgumentException::class);

it('requires a color when the field is required', function () {
    $field = FlexColorPickerField::make('accent')
        ->required()
        ->label('Accent color');

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('accent', null, function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->not->toBeNull();
});

it('builds flex color picker from flex field definition', function () {
    $builder = app(FlexFieldFormBuilder::class);

    $field = $builder->makeComponent(new FlexFieldDefinition(
        slug: 'brand_color',
        label: 'Brand color',
        type: FieldType::FlexColorPicker,
        config: [
            'layout' => 'grid',
            'format' => 'rgba',
            'alpha' => true,
            'eyedropper' => false,
            'grid_columns' => 10,
            'grid_rows' => 8,
            'grid_colors' => ['#111111', '#EEEEEE'],
            'size' => 'sm',
        ],
    ));

    expect($field)->toBeInstanceOf(FlexColorPickerField::class)
        ->and($field->getLayout())->toBe(FlexColorPickerField::LAYOUT_GRID)
        ->and($field->getFormat())->toBe(FlexColorPickerField::FORMAT_RGBA)
        ->and($field->isAlphaEnabled())->toBeTrue()
        ->and($field->isEyedropperEnabled())->toBeFalse()
        ->and($field->getGridColumns())->toBe(10)
        ->and($field->getGridRows())->toBe(8)
        ->and($field->getGridColors())->toBe(['#111111', '#EEEEEE'])
        ->and($field->getSize())->toBe('sm');
});

it('registers flex color picker playground defaults', function () {
    $state = app(FlexFieldsPlaygroundBuilder::class)->defaultState();

    expect($state)->toHaveKeys([
        'flex_color_picker__advanced',
        'flex_color_picker__grid',
        'flex_color_picker__secondary',
        'flex_color_picker__alpha',
        'flex_color_picker__hsl',
        'flex_color_picker__readonly',
    ]);
});
