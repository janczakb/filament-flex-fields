<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Data\FlexFieldSection;
use Bjanczak\FilamentFlexFields\Enums\FlexFieldSectionType;
use Closure;
use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Model;

final class FlexFieldSectionLayoutHelper
{
    public static function resolveRecord(?object $context): ?Model
    {
        return $context instanceof Model ? $context : null;
    }

    /**
     * @param  list<Component>  $components
     */
    public static function applySectionVisibilityToComponents(
        array $components,
        FlexFieldSection $sectionDefinition,
        string $statePathPrefix,
        ?Model $record,
    ): void {
        if ($sectionDefinition->visibleWhen === null) {
            return;
        }

        $visible = self::compileSectionVisibility($sectionDefinition, $statePathPrefix, $record);

        foreach ($components as $component) {
            $component->visible($visible);
        }
    }

    public static function applySectionVisibilityToSection(
        Section $section,
        FlexFieldSection $sectionDefinition,
        string $statePathPrefix,
        ?Model $record,
    ): void {
        if ($sectionDefinition->visibleWhen === null) {
            return;
        }

        $section->visible(self::compileSectionVisibility($sectionDefinition, $statePathPrefix, $record));
    }

    /**
     * @param  list<FlexFieldDefinition>  $definitions
     * @return list<FlexFieldDefinition>
     */
    public static function sortDefinitionsBySections(
        array $definitions,
        array $sections,
    ): array {
        if ($sections === []) {
            return $definitions;
        }

        $sectionOrder = [];

        foreach ($sections as $index => $section) {
            $sectionOrder[$section->id] = $section->sort !== 0 ? $section->sort : $index;
        }

        $ungroupedSort = count($sections) + 1;

        return collect($definitions)
            ->sortBy(function (FlexFieldDefinition $definition) use ($sectionOrder, $ungroupedSort): array {
                $sectionSort = $definition->sectionId !== null && isset($sectionOrder[$definition->sectionId])
                    ? $sectionOrder[$definition->sectionId]
                    : $ungroupedSort;

                return [$sectionSort, $definition->sort, $definition->slug];
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<Column>  $columns
     * @param  list<FlexFieldDefinition>  $definitions
     * @param  list<FlexFieldSection>  $sections
     * @return list<Column>
     */
    public static function labelColumnsBySections(array $columns, array $definitions, array $sections): array
    {
        if ($sections === []) {
            return $columns;
        }

        $sectionLabels = [];

        foreach ($sections as $section) {
            $sectionLabels[$section->id] = $section->label;
        }

        $columnsBySlug = [];

        foreach ($columns as $column) {
            $name = $column->getName();

            if (str_starts_with($name, 'flex_')) {
                $columnsBySlug[substr($name, 5)] = $column;
            }
        }

        $labeled = [];

        foreach ($definitions as $definition) {
            $column = $columnsBySlug[$definition->slug] ?? null;

            if ($column === null) {
                continue;
            }

            if ($definition->sectionId !== null && isset($sectionLabels[$definition->sectionId])) {
                $column->label($sectionLabels[$definition->sectionId].' · '.$definition->label);
            }

            $labeled[] = $column;
        }

        return $labeled !== [] ? $labeled : $columns;
    }

    /**
     * @param  callable(list<FlexFieldDefinition>): array<int, Entry|Column>  $buildItems
     * @return list<Section>
     */
    public static function buildSectionedLayout(
        array $sections,
        array $definitions,
        callable $buildItems,
        ?string $ungroupedLabel = null,
        bool $collapsible = false,
        ?Model $record = null,
        string $statePathPrefix = '',
    ): array {
        if ($sections === []) {
            return [];
        }

        $definitionsBySection = collect($definitions)->groupBy(fn (FlexFieldDefinition $definition): string => $definition->sectionId ?? '__ungrouped__');
        $layout = [];

        foreach ($sections as $sectionDefinition) {
            /** @var list<FlexFieldDefinition> $sectionFields */
            $sectionFields = $definitionsBySection->get($sectionDefinition->id, collect())->values()->all();

            if ($sectionFields === []) {
                continue;
            }

            $items = $buildItems($sectionFields);

            if ($sectionDefinition->type === FlexFieldSectionType::Headless) {
                $headless = Section::make('')
                    ->schema($items)
                    ->hiddenLabel()
                    ->compact();

                self::applySectionVisibilityToSection($headless, $sectionDefinition, $statePathPrefix, $record);
                $layout[] = $headless;

                continue;
            }

            $section = Section::make($sectionDefinition->label)
                ->schema($items);

            if ($sectionDefinition->type === FlexFieldSectionType::Fieldset) {
                $section->compact();
            }

            if ($collapsible && $sectionDefinition->type === FlexFieldSectionType::Section) {
                $section->collapsible();
            }

            if ($sectionDefinition->description !== null) {
                $section->description($sectionDefinition->description);
            }

            self::applySectionVisibilityToSection($section, $sectionDefinition, $statePathPrefix, $record);

            $layout[] = $section;
        }

        /** @var list<FlexFieldDefinition> $ungrouped */
        $ungrouped = $definitionsBySection->get('__ungrouped__', collect())->values()->all();

        if ($ungrouped !== []) {
            $ungroupedSection = Section::make($ungroupedLabel ?? __('filament-flex-fields::default.schema.custom_fields_section'))
                ->schema($buildItems($ungrouped));

            if ($collapsible) {
                $ungroupedSection->collapsible();
            }

            $layout[] = $ungroupedSection;
        }

        return $layout;
    }

    private static function compileSectionVisibility(
        FlexFieldSection $sectionDefinition,
        string $statePathPrefix,
        ?Model $record,
    ): Closure {
        return JsonFieldConditions::compileVisibleWhen(
            $sectionDefinition->visibleWhen ?? [],
            $statePathPrefix,
            $record,
        );
    }
}
