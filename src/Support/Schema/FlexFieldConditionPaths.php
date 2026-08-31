<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

final class FlexFieldConditionPaths
{
    public static function qualify(string $source, string $field): string
    {
        return match ($source) {
            'model' => str_starts_with($field, 'model.') ? $field : 'model.'.$field,
            'relation' => str_starts_with($field, 'relation.') ? $field : 'relation.'.$field,
            default => str_starts_with($field, 'flex_field.') ? $field : 'flex_field.'.$field,
        };
    }
}
