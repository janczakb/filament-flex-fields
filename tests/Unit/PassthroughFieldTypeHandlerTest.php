<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers\PassthroughFieldTypeHandler;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;

it('builds key value fields from form builder definitions', function (): void {
    $handler = new PassthroughFieldTypeHandler;
    $definition = new FlexFieldDefinition(
        slug: 'metadata',
        label: 'Metadata',
        type: FieldType::KeyValue,
    );

    $field = $handler->make($definition, 'metadata');

    expect($field)->toBeInstanceOf(KeyValue::class)
        ->and($field->getName())->toBe('metadata');
});

it('builds repeater fields from form builder definitions', function (): void {
    $handler = new PassthroughFieldTypeHandler;
    $definition = new FlexFieldDefinition(
        slug: 'line_items',
        label: 'Line items',
        type: FieldType::Repeater,
    );

    $field = $handler->make($definition, 'line_items');

    expect($field)->toBeInstanceOf(Repeater::class)
        ->and($field->getName())->toBe('line_items');
});

it('builds code fields as monospace textareas from form builder definitions', function (): void {
    $handler = new PassthroughFieldTypeHandler;
    $definition = new FlexFieldDefinition(
        slug: 'snippet',
        label: 'Snippet',
        type: FieldType::Code,
    );

    $field = $handler->make($definition, 'snippet');

    expect($field)->toBeInstanceOf(Textarea::class)
        ->and($field->getName())->toBe('snippet')
        ->and($field->getRows())->toBe(8)
        ->and($field->getExtraAttributes())->toBe(['class' => 'font-mono text-sm']);
});

it('builds json fields as monospace textareas from form builder definitions', function (): void {
    $handler = new PassthroughFieldTypeHandler;
    $definition = new FlexFieldDefinition(
        slug: 'payload',
        label: 'Payload',
        type: FieldType::Json,
    );

    $field = $handler->make($definition, 'payload');

    expect($field)->toBeInstanceOf(Textarea::class)
        ->and($field->getName())->toBe('payload')
        ->and($field->getRows())->toBe(8)
        ->and($field->getExtraAttributes())->toBe(['class' => 'font-mono text-sm']);
});

it('reports supported passthrough field types', function (): void {
    $handler = new PassthroughFieldTypeHandler;

    expect($handler->supports(FieldType::KeyValue))->toBeTrue()
        ->and($handler->supports(FieldType::Repeater))->toBeTrue()
        ->and($handler->supports(FieldType::Code))->toBeTrue()
        ->and($handler->supports(FieldType::Json))->toBeTrue()
        ->and($handler->supports(FieldType::SingleLineText))->toBeFalse();
});
