<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schema\Admin;

use Filament\Schemas\Components\Component;

final class FieldTypeAdminSchemaRegistry
{
    /**
     * @return list<Component>
     */
    public static function components(): array
    {
        return FieldTypeAutoAdminSchema::components();
    }
}
