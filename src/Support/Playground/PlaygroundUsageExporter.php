<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\CoverCard;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardGroup;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardStack;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

/**
 * Turns live playground components into copy-paste usage.php snippets.
 *
 * Prefers package layout/schema heroes (SegmentTabs, CoverCard, …) over nested
 * fields inside those layouts — otherwise hubs like segment-tabs wrongly export
 * the first FlexTextInput.
 */
final class PlaygroundUsageExporter
{
    private const MAX_FIELDS = 1;

    private const MAX_OPTION_ENTRIES = 6;

    private const MAX_STRING_LENGTH = 120;

    private const MAX_TAB_FIELDS = 3;

    /**
     * Hub slug → preferred root component class.
     *
     * @var array<string, class-string>
     */
    private const HUB_HEROES = [
        'segment-tabs' => SegmentTabs::class,
        'translatable-fields' => TranslatableFields::class,
        'cover-card' => CoverCard::class,
        'item-card-group' => ItemCardGroup::class,
    ];

    /**
     * Layout/schema components that should win over nested Field exports.
     *
     * @var list<class-string>
     */
    private const LAYOUT_HEROES = [
        SegmentTabs::class,
        TranslatableFields::class,
        CoverCard::class,
        ItemCardGroup::class,
        ItemCardStack::class,
        ItemCard::class,
    ];

    /**
     * @param  list<Component>  $components
     */
    public static function fromComponents(array $components, ?string $hubSlug = null): ?string
    {
        $layout = self::collectLayoutHeroes($components);
        $preferredClass = $hubSlug !== null ? (self::HUB_HEROES[$hubSlug] ?? null) : null;

        if ($preferredClass !== null) {
            foreach ($layout as $component) {
                if ($component instanceof $preferredClass) {
                    return self::exportComponentTree($component);
                }
            }
        }

        if ($layout !== []) {
            return self::exportComponentTree($layout[0]);
        }

        $fields = self::collectFields($components);

        if ($fields === []) {
            return null;
        }

        $fields = array_slice($fields, 0, self::MAX_FIELDS);
        $uses = [];
        $blocks = [];

        foreach ($fields as $field) {
            $exported = self::exportField($field);

            if ($exported === null) {
                continue;
            }

            $uses[$exported['class']] = true;
            $blocks[] = $exported['code'];
        }

        if ($blocks === []) {
            return null;
        }

        return self::assemble($uses, $blocks);
    }

    /**
     * @param  array<class-string, true>  $uses
     * @param  list<string>  $blocks
     */
    private static function assemble(array $uses, array $blocks): string
    {
        $useLines = array_map(
            static fn (string $class): string => 'use '.$class.';',
            array_keys($uses),
        );

        return implode("\n", $useLines)."\n\n".implode("\n\n", $blocks);
    }

    private static function exportComponentTree(Component $component): ?string
    {
        if ($component instanceof SegmentTabs) {
            return self::exportSegmentTabs($component);
        }

        if ($component instanceof Field) {
            $exported = self::exportField($component);

            return $exported === null
                ? null
                : self::assemble([$exported['class'] => true], [$exported['code']]);
        }

        // Generic package layout: Class::make('Label')->… when we only know the label.
        if (self::isPackageComponent($component)) {
            $class = $component::class;
            $short = class_basename($class);
            $label = self::stringish(method_exists($component, 'getLabel') ? $component->getLabel() : null) ?? $short;
            $lines = ["{$short}::make(".self::exportValue($label).')'];

            if (method_exists($component, 'getVariant')) {
                try {
                    $variant = $component->getVariant();

                    if (is_string($variant) && $variant !== '' && $variant !== 'default') {
                        $lines[] = '    ->variant('.self::exportValue($variant).')';
                    }
                } catch (\Throwable) {
                    //
                }
            }

            return self::assemble([$class => true], [implode("\n", $lines).';']);
        }

        return null;
    }

    private static function exportSegmentTabs(SegmentTabs $tabs): ?string
    {
        $uses = [
            SegmentTabs::class => true,
            SegmentTab::class => true,
        ];

        $label = self::stringish($tabs->getLabel()) ?? 'Tabs';
        $lines = ['SegmentTabs::make('.self::exportValue($label).')'];

        try {
            $variant = $tabs->getVariant();

            if (is_string($variant) && $variant !== '' && $variant !== 'default') {
                $lines[] = '    ->variant('.self::exportValue($variant).')';
            }
        } catch (\Throwable) {
            //
        }

        try {
            if (! $tabs->hasSeparators()) {
                $lines[] = '    ->separators(false)';
            }
        } catch (\Throwable) {
            //
        }

        try {
            if ($tabs->isFullWidth()) {
                $lines[] = '    ->fullWidth()';
            }
        } catch (\Throwable) {
            //
        }

        $tabBlocks = [];

        foreach (self::childComponents($tabs) as $tab) {
            if (! $tab instanceof SegmentTab) {
                continue;
            }

            $tabLabel = self::stringish($tab->getLabel()) ?? 'Tab';
            $tabLines = ['        SegmentTab::make('.self::exportValue($tabLabel).')'];

            if (method_exists($tab, 'getIcon')) {
                try {
                    $icon = $tab->getIcon();
                    $iconExport = self::exportIcon($icon);

                    if ($iconExport !== null) {
                        $tabLines[] = '            ->icon('.$iconExport['code'].')';

                        foreach ($iconExport['uses'] as $useClass) {
                            $uses[$useClass] = true;
                        }
                    }
                } catch (\Throwable) {
                    //
                }
            }

            $schemaFields = [];

            foreach (self::childComponents($tab) as $child) {
                if (! $child instanceof Field) {
                    continue;
                }

                $exported = self::exportField($child, indent: 4);

                if ($exported === null) {
                    continue;
                }

                $uses[$exported['class']] = true;
                $schemaFields[] = $exported['code'];

                if (count($schemaFields) >= self::MAX_TAB_FIELDS) {
                    break;
                }
            }

            if ($schemaFields !== []) {
                $tabLines[] = '            ->schema([';
                $tabLines[] = implode(",\n", $schemaFields).',';
                $tabLines[] = '            ])';
            }

            $tabBlocks[] = implode("\n", $tabLines);
        }

        if ($tabBlocks === []) {
            return null;
        }

        $lines[] = '    ->tabs([';
        $lines[] = implode(",\n", $tabBlocks).',';
        $lines[] = '    ]);';

        return self::assemble($uses, [implode("\n", $lines)]);
    }

    /**
     * @return array{code: string, uses: list<class-string>}|null
     */
    private static function exportIcon(mixed $icon): ?array
    {
        if ($icon instanceof BackedEnum) {
            return [
                'code' => self::exportValue($icon->value) ?? 'null',
                'uses' => [],
            ];
        }

        if (is_string($icon) && $icon !== '') {
            if (str_starts_with($icon, 'gravityui-')) {
                return [
                    'code' => self::exportValue($icon) ?? 'null',
                    'uses' => [],
                ];
            }

            return [
                'code' => self::exportValue($icon) ?? 'null',
                'uses' => [],
            ];
        }

        return null;
    }

    /**
     * @param  list<Component>  $components
     * @return list<Component>
     */
    private static function collectLayoutHeroes(array $components): array
    {
        $heroes = [];

        $walk = function (Component $component) use (&$walk, &$heroes): void {
            if ($component instanceof Section || $component instanceof Grid) {
                foreach (self::childComponents($component) as $child) {
                    $walk($child);
                }

                return;
            }

            foreach (self::LAYOUT_HEROES as $class) {
                if ($component instanceof $class) {
                    $heroes[] = $component;

                    return;
                }
            }

            // Keep walking wrappers that are not heroes (e.g. related-hubs views skip).
            foreach (self::childComponents($component) as $child) {
                $walk($child);
            }
        };

        foreach ($components as $component) {
            $walk($component);
        }

        return $heroes;
    }

    /**
     * @param  list<Component>  $components
     * @return list<Field>
     */
    private static function collectFields(array $components): array
    {
        $fields = [];

        $walk = function (Component $component) use (&$walk, &$fields): void {
            if ($component instanceof Field) {
                $fields[] = $component;

                return;
            }

            // Do not dive into layout heroes when collecting fallback fields —
            // those hubs should have been handled by layout export.
            foreach (self::LAYOUT_HEROES as $class) {
                if ($component instanceof $class) {
                    return;
                }
            }

            foreach (self::childComponents($component) as $child) {
                $walk($child);
            }
        };

        foreach ($components as $component) {
            $walk($component);
        }

        return $fields;
    }

    /**
     * @return list<Component>
     */
    private static function childComponents(Component $component): array
    {
        if (! method_exists($component, 'getDefaultChildComponents')) {
            return [];
        }

        try {
            /** @var list<Component>|mixed $children */
            $children = $component->getDefaultChildComponents();
        } catch (\Throwable) {
            return [];
        }

        if (! is_array($children)) {
            return [];
        }

        return array_values(array_filter(
            $children,
            static fn (mixed $child): bool => $child instanceof Component,
        ));
    }

    private static function isPackageComponent(Component $component): bool
    {
        return str_starts_with($component::class, 'Bjanczak\\FilamentFlexFields\\');
    }

    /**
     * @return array{class: class-string, code: string}|null
     */
    private static function exportField(Field $field, int $indent = 0): ?array
    {
        $class = $field::class;
        $short = class_basename($class);
        $name = self::sanitizeName((string) $field->getName());
        $pad = str_repeat('    ', $indent);

        $lines = ["{$pad}{$short}::make('{$name}')"];

        foreach (self::fluentCalls($field) as $call) {
            $lines[] = $pad.'    ->'.$call;
        }

        return [
            'class' => $class,
            'code' => implode("\n", $lines),
        ];
    }

    /**
     * @return list<string>
     */
    private static function fluentCalls(Field $field): array
    {
        $calls = [];

        try {
            $label = self::stringish($field->getLabel());

            if ($label !== null && $label !== '') {
                $calls[] = 'label('.self::exportValue($label).')';
            }
        } catch (\Throwable) {
            //
        }

        try {
            $helper = method_exists($field, 'getHelperText') ? self::stringish($field->getHelperText()) : null;

            if (is_string($helper) && $helper !== '' && ! self::isPlaygroundNoise($helper)) {
                $calls[] = 'helperText('.self::exportValue($helper).')';
            }
        } catch (\Throwable) {
            //
        }

        if (method_exists($field, 'getSize')) {
            try {
                $size = $field->getSize();

                if (is_string($size) && $size !== '' && $size !== 'md') {
                    $calls[] = 'size('.self::exportValue($size).')';
                }
            } catch (\Throwable) {
                //
            }
        }

        if (method_exists($field, 'getSectionLabel')) {
            try {
                $sectionLabel = $field->getSectionLabel();

                if (is_string($sectionLabel) && $sectionLabel !== '') {
                    $calls[] = 'sectionLabel('.self::exportValue($sectionLabel).')';
                }
            } catch (\Throwable) {
                //
            }
        }

        if (method_exists($field, 'getColors') && method_exists($field, 'hasTooltips')) {
            try {
                $colors = $field->getColors();

                if (is_array($colors) && $colors !== []) {
                    if ($field->hasTooltips()) {
                        $tooltips = [];

                        foreach (array_keys($colors) as $key) {
                            $tooltips[(string) $key] = method_exists($field, 'getColorLabel')
                                ? (string) $field->getColorLabel((string) $key)
                                : (string) $key;
                        }

                        $calls[] = 'tooltips('.self::exportValue($tooltips).')';
                    }

                    $calls[] = 'colors('.self::exportValue($colors).')';
                }
            } catch (\Throwable) {
                //
            }
        }

        if (method_exists($field, 'getNormalizedOptions')) {
            try {
                $options = self::exportOptions($field->getNormalizedOptions());

                if ($options !== null) {
                    $calls[] = 'options('.$options.')';
                }
            } catch (\Throwable) {
                //
            }
        } elseif (method_exists($field, 'getOptions')) {
            try {
                $options = $field->getOptions();

                if (is_array($options) && $options !== []) {
                    $calls[] = 'options('.self::exportValue(self::truncateAssociative($options)).')';
                }
            } catch (\Throwable) {
                //
            }
        }

        foreach ([
            'getLayout' => 'layout',
            'getIndicator' => 'indicator',
            'getVariant' => 'variant',
            'getRounding' => 'rounding',
            'getImageAspectRatio' => 'imageAspectRatio',
            'getImageFit' => 'imageFit',
        ] as $getter => $method) {
            if (! method_exists($field, $getter)) {
                continue;
            }

            try {
                $value = $field->{$getter}();
            } catch (\Throwable) {
                continue;
            }

            if (! is_string($value) || $value === '' || self::isDefaultFluentValue($method, $value)) {
                continue;
            }

            $calls[] = $method.'('.self::exportValue($value).')';
        }

        if (method_exists($field, 'getGridColumnConfig')) {
            try {
                $columns = $field->getGridColumnConfig();

                if (is_array($columns) && self::isNonTrivialGrid($columns)) {
                    $calls[] = 'gridColumns('.self::exportValue($columns).')';
                }
            } catch (\Throwable) {
                //
            }
        }

        if (method_exists($field, 'isMultiple')) {
            try {
                if ($field->isMultiple()) {
                    $calls[] = 'multiple()';
                }
            } catch (\Throwable) {
                // Needs a live container — skip.
            }
        }

        if (method_exists($field, 'getMinSelections')) {
            try {
                $min = $field->getMinSelections();

                if (is_int($min) && $min > 0) {
                    $calls[] = 'minSelections('.$min.')';
                }
            } catch (\Throwable) {
                //
            }
        }

        if (method_exists($field, 'getMaxSelections')) {
            try {
                $max = $field->getMaxSelections();

                if (is_int($max) && $max > 0) {
                    $calls[] = 'maxSelections('.$max.')';
                }
            } catch (\Throwable) {
                //
            }
        }

        if (method_exists($field, 'isRequired')) {
            try {
                if ($field->isRequired()) {
                    $calls[] = 'required()';
                }
            } catch (\Throwable) {
                //
            }
        }

        try {
            $default = $field->getDefaultState();

            if ($default !== null && $default !== '' && $default !== [] && ! $default instanceof \Closure) {
                $exportedDefault = self::exportValue($default);

                if ($exportedDefault !== null) {
                    $calls[] = 'default('.$exportedDefault.')';
                }
            }
        } catch (\Throwable) {
            //
        }

        return $calls;
    }

    /**
     * @param  array<array-key, mixed>  $options
     */
    private static function exportOptions(array $options): ?string
    {
        if ($options === []) {
            return null;
        }

        $simplified = [];
        $count = 0;

        foreach ($options as $key => $option) {
            if ($count >= self::MAX_OPTION_ENTRIES) {
                break;
            }

            if (is_string($option) || is_int($option) || is_float($option) || is_bool($option) || $option === null) {
                $simplified[$key] = $option;
                $count++;

                continue;
            }

            if (! is_array($option)) {
                continue;
            }

            $row = [];

            if (isset($option['label'])) {
                $row['label'] = self::stringish($option['label']) ?? (string) $option['label'];
            }

            if (isset($option['description']) && is_string($option['description']) && $option['description'] !== '') {
                $row['description'] = $option['description'];
            }

            if (isset($option['image']) && is_string($option['image']) && $option['image'] !== '') {
                $row['image'] = self::sanitizeImageUrl($option['image']);
            }

            if (isset($option['icon']) && (is_string($option['icon']) || $option['icon'] instanceof BackedEnum)) {
                $row['icon'] = $option['icon'] instanceof BackedEnum
                    ? $option['icon']->value
                    : $option['icon'];
            }

            if (isset($option['price']) && is_string($option['price'])) {
                $row['price'] = $option['price'];
            }

            if (isset($option['price_suffix']) && is_string($option['price_suffix'])) {
                $row['price_suffix'] = $option['price_suffix'];
            }

            if (isset($option['badge']) && is_string($option['badge'])) {
                $row['badge'] = $option['badge'];
            }

            if (isset($option['disabled']) && $option['disabled'] === true) {
                $row['disabled'] = true;
            }

            if ($row === []) {
                continue;
            }

            if (array_keys($row) === ['label']) {
                $simplified[$key] = $row['label'];
            } else {
                $simplified[$key] = $row;
            }

            $count++;
        }

        if ($simplified === []) {
            return null;
        }

        return self::exportValue($simplified);
    }

    private static function sanitizeImageUrl(string $url): string
    {
        if (str_starts_with($url, 'data:') || strlen($url) > self::MAX_STRING_LENGTH) {
            return 'https://example.com/image.jpg';
        }

        return $url;
    }

    private static function sanitizeName(string $name): string
    {
        $name = str_replace(['.', '->'], '_', $name);

        if (str_contains($name, '__')) {
            $parts = explode('__', $name);
            $name = (string) end($parts);
        }

        $name = trim($name);

        return $name !== '' ? $name : 'field';
    }

    private static function stringish(mixed $value): ?string
    {
        if ($value === null || $value instanceof \Closure) {
            return null;
        }

        if ($value instanceof Htmlable) {
            $value = $value->toHtml();
        }

        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }

        return null;
    }

    private static function isPlaygroundNoise(string $helper): bool
    {
        return (bool) preg_match('/\b(layout\(|gridColumns\(|variant\(|indicator\(|helperText\()/i', $helper);
    }

    private static function isDefaultFluentValue(string $method, string $value): bool
    {
        return match ($method) {
            'variant' => $value === 'default',
            'rounding' => $value === 'md',
            'imageFit' => $value === 'cover',
            default => false,
        };
    }

    /**
     * @param  array<string, int>  $columns
     */
    private static function isNonTrivialGrid(array $columns): bool
    {
        $values = array_values($columns);

        return count(array_unique($values)) > 1 || ($values[0] ?? 1) !== 1;
    }

    /**
     * @param  array<array-key, mixed>  $value
     * @return array<array-key, mixed>
     */
    private static function truncateAssociative(array $value): array
    {
        $out = [];
        $count = 0;

        foreach ($value as $key => $item) {
            if ($count >= self::MAX_OPTION_ENTRIES) {
                break;
            }

            $out[$key] = $item;
            $count++;
        }

        return $out;
    }

    private static function exportValue(mixed $value): ?string
    {
        if ($value instanceof \Closure) {
            return null;
        }

        if ($value instanceof BackedEnum) {
            return self::exportValue($value->value);
        }

        if ($value instanceof UnitEnum) {
            return self::exportValue($value->name);
        }

        if (is_string($value)) {
            if (strlen($value) > self::MAX_STRING_LENGTH) {
                $value = substr($value, 0, self::MAX_STRING_LENGTH - 1).'…';
            }

            return var_export($value, true);
        }

        if (is_int($value) || is_float($value)) {
            return var_export($value, true);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (! is_array($value)) {
            return null;
        }

        return self::exportArray($value, 1);
    }

    /**
     * @param  array<array-key, mixed>  $value
     */
    private static function exportArray(array $value, int $depth): ?string
    {
        if ($value === []) {
            return '[]';
        }

        $isList = array_is_list($value);
        $parts = [];
        $indent = str_repeat('    ', $depth);
        $closingIndent = str_repeat('    ', max(0, $depth - 1));

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $exported = self::exportArray($item, $depth + 1);
            } else {
                $exported = self::exportValue($item);
            }

            if ($exported === null) {
                continue;
            }

            if ($isList) {
                $parts[] = $exported;

                continue;
            }

            $keyExport = self::exportValue((string) $key);

            if ($keyExport === null) {
                continue;
            }

            $parts[] = $keyExport.' => '.$exported;
        }

        if ($parts === []) {
            return '[]';
        }

        $inner = implode(",\n".$indent, $parts);

        return "[\n{$indent}{$inner},\n{$closingIndent}]";
    }
}
