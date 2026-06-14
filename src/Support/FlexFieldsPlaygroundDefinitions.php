<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;

class FlexFieldsPlaygroundDefinitions
{
    /**
     * Fields implemented and ready to preview in the playground.
     * Add types here one by one as we build them.
     *
     * @return list<FieldType>
     */
    public static function implementedTypes(): array
    {
        return [
            FieldType::NumberStepper,
            FieldType::SegmentControl,
        ];
    }

    /**
     * @return list<FlexFieldDefinition>
     */
    public static function implemented(): array
    {
        return array_map(
            fn (FieldType $type): FlexFieldDefinition => self::forType($type),
            self::implementedTypes(),
        );
    }

    public static function forType(FieldType $type): FlexFieldDefinition
    {
        return FlexFieldDefinition::fromArray([
            'slug' => $type->value,
            'label' => str($type->value)->replace('_', ' ')->title()->toString(),
            'type' => $type,
            'config' => self::demoConfig($type),
            'help_text' => "Preview: {$type->value}",
            'default_value' => self::demoDefault($type),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function demoConfig(FieldType $type): array
    {
        $base = $type->defaultConfig();

        return match ($type) {
            FieldType::SegmentControl => array_merge($base, [
                'options' => [
                    'a' => 'Option A',
                    'b' => 'Option B',
                    'c' => 'Option C',
                ],
                'full_width' => true,
            ]),
            FieldType::NumberStepper => array_merge($base, [
                'min' => 0,
                'max' => 10,
                'step' => 1,
            ]),
            default => $base,
        };
    }

    protected static function demoDefault(FieldType $type): mixed
    {
        return match ($type) {
            FieldType::SegmentControl => 'a',
            FieldType::NumberStepper => 1,
            default => null,
        };
    }
}
