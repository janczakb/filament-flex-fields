<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schemas\Components;

use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\Concerns\BuildsTranslatableTabs;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\Concerns\ConfiguresTranslatableLocales;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\Concerns\CustomizesTranslatableComponents;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\Concerns\HasTranslatableMigrationAliases;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\Concerns\ProvidesTranslatablePresets;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\Concerns\SyncsTranslatableLocaleFieldState;

/**
 * Locale-aware form layout built on SegmentTabs.
 *
 * Designed as a universal, extensible alternative to third-party translatable tab
 * packages — with explicit extension points (modifiers, localeFieldUsing, storageAttributeUsing)
 * and first-class JSON / Spatie hydration.
 */
class TranslatableFields extends SegmentTabs
{
    use BuildsTranslatableTabs;
    use ConfiguresTranslatableLocales;
    use CustomizesTranslatableComponents;
    use HasTranslatableMigrationAliases;
    use ProvidesTranslatablePresets;
    use SyncsTranslatableLocaleFieldState;

    protected function setUp(): void
    {
        parent::setUp();

        $this->separators(false);
        $this->extraAttributes([
            'class' => 'fff-translatable-fields',
        ]);

        $this->tabs(fn (TranslatableFields $component): array => $component->buildTranslatableTabs());
    }
}
