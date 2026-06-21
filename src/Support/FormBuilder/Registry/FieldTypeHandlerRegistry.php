<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Registry;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\AppliesFieldDefinitionMeta;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldTypeHandler;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers\ChoiceFieldTypeHandler;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers\ColorFieldTypeHandler;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers\DateTimeFieldTypeHandler;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers\MediaFieldTypeHandler;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers\NumericFieldTypeHandler;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers\PassthroughFieldTypeHandler;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers\TextFieldTypeHandler;
use Filament\Schemas\Components\Component;
use InvalidArgumentException;

final class FieldTypeHandlerRegistry
{
    use AppliesFieldDefinitionMeta;

    /** @var list<FieldTypeHandler> */
    private readonly array $handlers;

    public function __construct(
        ?TextFieldTypeHandler $text = null,
        ?NumericFieldTypeHandler $numeric = null,
        ?ChoiceFieldTypeHandler $choice = null,
        ?DateTimeFieldTypeHandler $dateTime = null,
        ?MediaFieldTypeHandler $media = null,
        ?ColorFieldTypeHandler $color = null,
        ?PassthroughFieldTypeHandler $passthrough = null,
    ) {
        $this->handlers = [
            $text ?? new TextFieldTypeHandler,
            $numeric ?? new NumericFieldTypeHandler,
            $choice ?? new ChoiceFieldTypeHandler,
            $dateTime ?? new DateTimeFieldTypeHandler,
            $media ?? new MediaFieldTypeHandler,
            $color ?? new ColorFieldTypeHandler,
            $passthrough ?? new PassthroughFieldTypeHandler,
        ];
    }

    public function resolve(FieldType $type): FieldTypeHandler
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($type)) {
                return $handler;
            }
        }

        throw new InvalidArgumentException("No handler registered for field type [{$type->value}].");
    }

    public function makeComponent(FlexFieldDefinition $definition, string $statePathPrefix = 'flex_field_values'): Component
    {
        $statePath = filled($statePathPrefix)
            ? "{$statePathPrefix}.{$definition->slug}"
            : $definition->slug;

        $field = $this->resolve($definition->type)->make($definition, $statePath);

        return $this->applyDefinitionMeta($field, $definition);
    }
}
