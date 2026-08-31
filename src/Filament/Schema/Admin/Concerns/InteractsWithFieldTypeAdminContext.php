<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schema\Admin\Concerns;

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Filament\Schemas\Components\Utilities\Get;

trait InteractsWithFieldTypeAdminContext
{
    protected static function selectedType(Get $get): ?FieldType
    {
        $type = $get('type');

        if ($type instanceof FieldType) {
            return $type;
        }

        if (! is_string($type) || $type === '') {
            return null;
        }

        return FieldType::tryFrom($type);
    }

    protected static function isType(Get $get, FieldType ...$types): bool
    {
        $selected = self::selectedType($get);

        if ($selected === null) {
            return false;
        }

        return in_array($selected, $types, true);
    }

    protected static function isOneOf(Get $get, array $types): bool
    {
        $selected = self::selectedType($get);

        if ($selected === null) {
            return false;
        }

        return in_array($selected, $types, true);
    }
}
