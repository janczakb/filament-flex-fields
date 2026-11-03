<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Assets;

use Bjanczak\FilamentFlexFields\Support\Assets\Rendering\SchemaRendererIdentity;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use WeakMap;

/** Prepare only rendered native tabs that contain an initially visible FLEX surface. */
final class InitialSchemaLayout
{
    /** @var WeakMap<Tabs, bool>|null */
    private static ?WeakMap $preparing = null;

    public static function register(): void
    {
        // Root Schema hooks also run on native-only pages and on item templates.
        // Native tab rendering supplies the final item/container context instead.
        Tabs::configureUsing(static function (Tabs $tabs): void {
            $tabs->extraAttributes(static fn (Tabs $component): array => self::prepareTabs($component), merge: true);
        });
    }

    /** Explicit preparation remains available to standalone render integrations. */
    public static function prepare(Schema $schema): void
    {
        if (request()->hasHeader('X-Livewire') || $schema->getParentComponent() !== null) {
            return;
        }

        self::visitTabs($schema);
    }

    private static function visitTabs(Schema $schema): void
    {
        foreach ($schema->getComponents(withHidden: true) as $component) {
            if (! $component instanceof Component) {
                continue;
            }
            if ($component instanceof Tabs) {
                if ($attributes = self::prepareTabs($component)) {
                    $component->extraAttributes($attributes, merge: true);
                }
            }
            if (self::isDormantLayout($component)) {
                continue;
            }
            foreach ($component->getChildSchemas(withHidden: true) as $children) {
                self::visitTabs($children);
            }
        }
    }

    /** @return array<string, string> */
    private static function prepareTabs(Tabs $group): array
    {
        self::$preparing ??= new WeakMap;
        if (request()->hasHeader('X-Livewire') || isset(self::$preparing[$group])
            || ! $group->isScrollable() || $group->isTabPersisted() || filled($group->getLivewireProperty())) {
            return [];
        }
        self::$preparing[$group] = true;
        try {
            if (! InitialSchemaVisibility::allows($group)
                || ! ($children = $group->getChildSchema())
                || ! self::containsVisibleFlex($children)) {
                return [];
            }
            foreach ($children->getComponents(withHidden: true) as $tab) {
                if (! $tab instanceof Tab || ! InitialSchemaVisibility::allows($tab)) {
                    continue;
                }
                if (! preg_match('/(?:^|\s)fi-active(?:\s|$)/', (string) ($tab->getExtraAttributes()['class'] ?? ''))) {
                    $tab->extraAttributes(['class' => 'fi-active'], merge: true);
                }
            }

            return ['data-fff-initial-layout' => 'native-tabs'];
        } finally {
            unset(self::$preparing[$group]);
        }
    }

    private static function containsVisibleFlex(Schema $schema): bool
    {
        foreach ($schema->getComponents(withHidden: true) as $component) {
            if (! $component instanceof Component) {
                continue;
            }
            if (SchemaRendererIdentity::isFlex($component) && InitialSchemaVisibility::allows($component)) {
                return true;
            }
            if (self::isDormantLayout($component)) {
                continue;
            }
            // Filament owns item cloning, record/state binding and unused block exclusion.
            // Never walk getChildSchema() here: that is an unbound item/block template.
            foreach ($component->getChildSchemas(withHidden: true) as $children) {
                if (self::containsVisibleFlex($children)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function isDormantLayout(Component $component): bool
    {
        // Check boundaries before resolving their child closures. Ordinary native
        // entry/field visibility is left to Filament, not speculatively invoked.
        return ($component instanceof Tab || $component instanceof Step || $component instanceof Section)
            && ! InitialSchemaVisibility::allows($component);
    }
}
