<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

use Bjanczak\FilamentFlexFields\Filament\Pages\FlexFieldManagementPage;
use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Filament\Panel;

final class FlexFieldGroupResourceRegistrar
{
    public static function register(Panel $panel): void
    {
        if (! FlexFieldsConfig::isSchemaResourceEnabled()) {
            return;
        }

        $panel->resources([
            FlexFieldGroupResource::class,
        ]);

        if (FlexFieldsConfig::isSchemaManagementPageEnabled()) {
            $panel->pages([
                FlexFieldManagementPage::class,
            ]);
        }
    }

    public static function isEnabled(): bool
    {
        return FlexFieldsConfig::isSchemaResourceEnabled();
    }
}
