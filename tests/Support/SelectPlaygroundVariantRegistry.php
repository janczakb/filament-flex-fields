<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\Support;

/**
 * Canonical map of every SelectField (and parity Select) demo on the select-field playground.
 * Used to generate atomic Livewire / DB / clear-empty matrices.
 *
 * Compatibility matrix (forbidden / soft collisions — assert in SelectField / eligibility tests):
 * - native + searchable|multiple|allowHtml → InvalidArgumentException (fail-fast)
 * - inlineSearch + multiple → inlineSearch ignored (hasInlineSearch false)
 * - item-card + clearable default → clearable false unless explicitly overridden
 * - keepSelectedInDropdown + single → no-op
 * - paginatedSearchResults without getSearchResultsPageUsing → first page only, hasMore=false
 * - allowCreateOption off → free-text values are not accepted from the create row (UI); package
 *   static-options validation rejects unknown keys (Rule::in-equivalent) unless relationship /
 *   getSearchResultsUsing; allowCreateOption on → created string keys are intentional
 * - UserSelect optionModel + static options → model wins (Studio configurator)
 * - searchable menu open + second field → exclusive close via flex-dropdown coordinator
 *
 * @phpstan-type Variant array{
 *     label: string,
 *     multiple: bool,
 *     clearable: bool,
 *     live: bool,
 *     searchable: bool,
 *     options: array<string|int, mixed>,
 *     filled: mixed,
 *     alternate: mixed,
 *     empty: mixed,
 *     flags?: list<string>
 * }
 */
final class SelectPlaygroundVariantRegistry
{
    /**
     * @return array<string, Variant>
     */
    public static function all(): array
    {
        $status = [
            'draft' => 'Draft',
            'reviewing' => 'Reviewing',
            'published' => 'Published',
        ];

        $tech = [
            'tailwind' => 'Tailwind CSS',
            'alpine' => 'Alpine.js',
            'laravel' => 'Laravel',
            'livewire' => 'Livewire',
        ];

        $genres = [
            'action' => 'Action',
            'adventure' => 'Adventure',
            'comedy' => 'Comedy',
            'drama' => 'Drama',
            'horror' => 'Horror',
            'thriller' => 'Thriller',
        ];

        $states = [
            'california' => 'California',
            'texas' => 'Texas',
            'delaware' => 'Delaware',
            'florida' => 'Florida',
            'new_york' => 'New York',
        ];

        $long = [
            'short' => 'Krótka opcja',
            'medium' => 'Średnio długa nazwa',
            'enterprise_agreement' => 'Enterprise Master Service Agreement with dedicated onboarding, premium support and custom SLA terms',
        ];

        $people = [
            'jane' => 'Jane Cooper',
            'john' => 'John Smith',
            'fred' => 'Fred',
            'alex' => 'Alex Rivera',
        ];

        $rich = [
            'pro' => 'Pro',
            'team' => 'Team',
            'free' => 'Free',
        ];

        $themes = [
            'sky' => 'Sky',
            'mint' => 'Mint',
            'rose' => 'Rose',
        ];

        $citiesAr = [
            'riyadh' => 'الرياض',
            'jeddah' => 'جدة',
            'dammam' => 'الدمام',
        ];

        $techAr = [
            'tailwind' => 'تايلويند CSS',
            'laravel' => 'لارavel',
            'livewire' => 'Livewire',
            'alpine' => 'Alpine.js',
        ];

        $citiesHe = [
            'tel_aviv' => 'תל אביב',
            'jerusalem' => 'ירושלים',
            'haifa' => 'חיפה',
        ];

        $domains = [
            'acme' => 'acme',
            'wyachts' => 'wyachts',
            'flex' => 'flex',
        ];

        $animals = [
            'dog' => 'Dog',
            'cat' => 'Cat',
            'bird' => 'Bird',
            'kangaroo' => 'Kangaroo',
        ];

        $countries = [
            'usa' => 'United States',
            'canada' => 'Canada',
            'uk' => 'United Kingdom',
            'france' => 'France',
        ];

        $single = static fn (string $label, array $options, mixed $filled, mixed $alternate, array $extra = []): array => array_merge([
            'label' => $label,
            'multiple' => false,
            'clearable' => true,
            'live' => false,
            'searchable' => false,
            'options' => $options,
            'filled' => $filled,
            'alternate' => $alternate,
            'empty' => null,
            'flags' => [],
        ], $extra);

        $multi = static fn (string $label, array $options, array $filled, array $alternate, array $extra = []): array => array_merge([
            'label' => $label,
            'multiple' => true,
            'clearable' => true,
            'live' => false,
            'searchable' => true,
            'options' => $options,
            'filled' => $filled,
            'alternate' => $alternate,
            'empty' => [],
            'flags' => [],
        ], $extra);

        return [
            'select__long_labels' => $single('Long option labels', $long, 'enterprise_agreement', 'short', ['searchable' => true, 'flags' => ['long_labels']]),
            'select__basic' => $single('Status', $status, 'published', 'draft'),
            'select__searchable' => $single('Searchable technologies', $tech, 'tailwind', 'laravel', ['searchable' => true]),
            'select__multiple' => $multi('Multiple genres', $genres, ['action', 'adventure', 'drama', 'comedy'], ['horror', 'thriller'], ['flags' => ['chip_mode']]),
            'select__multiple_checklist' => $multi('Multiple states checklist', $states, ['california', 'texas', 'delaware'], ['florida', 'new_york'], ['flags' => ['checklist']]),
            'select__email_recipients' => $multi('Email recipients', $people, ['jane', 'john'], ['alex', 'fred'], ['flags' => ['rich', 'checklist', 'chip_label']]),
            'select__custom_value_user' => $single('Custom value optionView', $people, 'fred', 'jane', ['searchable' => true, 'flags' => ['option_view']]),
            'select__grouped' => $single('Country sections', $countries, 'usa', 'uk', ['searchable' => true, 'flags' => ['grouped']]),
            'select__disabled_animals' => $single('Disabled animals', $animals, 'dog', 'bird', ['searchable' => true, 'flags' => ['disabled_options']]),
            'select__async_paginated' => $single('Async paginated', ['luke' => 'Luke', 'leia' => 'Leia', 'han' => 'Han'], 'luke', 'leia', ['searchable' => true, 'flags' => ['async_paginated']]),
            'select__disabled_options' => $single('Disabled options', $status, 'draft', 'reviewing', ['flags' => ['disable_option_when']]),
            'select__dynamic_options' => $single('Dynamic options', $status, 'published', 'draft', ['searchable' => true, 'flags' => ['dynamic_options']]),
            'select__truncate_labels' => $single('Truncated labels', $long, 'enterprise_agreement', 'medium', ['searchable' => true, 'flags' => ['truncate']]),
            'select__reorderable' => $multi('Reorderable multi', $tech, ['tailwind', 'laravel', 'livewire', 'alpine'], ['alpine', 'livewire', 'laravel', 'tailwind'], ['flags' => ['reorderable']]),
            'select__boolean' => $single('Boolean select', [1 => 'Yes', 0 => 'No'], true, false, ['flags' => ['boolean']]),
            'select__rich' => $single('Rich options', $rich, 'pro', 'team', ['searchable' => true, 'flags' => ['rich']]),
            'select__rich_icon_title' => $single('Rich icon+title', $rich, 'pro', 'free', ['flags' => ['rich']]),
            'select__rich_title_desc' => $single('Rich title+desc', $rich, 'pro', 'team', ['flags' => ['rich']]),
            'select__grid' => $single('Theme grid', $themes, 'sky', 'mint', ['flags' => ['grid']]),
            'select__disabled' => $single('Disabled', $status, 'published', 'draft', ['clearable' => false, 'flags' => ['disabled_field']]),
            'select__required' => $single('Required status', $status, 'published', 'draft', ['flags' => ['required']]),
            'select__sm' => $single('Small', $status, 'draft', 'published', ['flags' => ['size:sm']]),
            'select__md' => $single('Medium', $status, 'reviewing', 'draft', ['flags' => ['size:md']]),
            'select__lg' => $single('Large', $status, 'published', 'reviewing', ['flags' => ['size:lg']]),
            'select__multiple_sm' => $multi('Multiple chips sm', $genres, ['action', 'adventure', 'drama', 'comedy'], ['horror'], ['flags' => ['size:sm']]),
            'select__multiple_md' => $multi('Multiple chips md', $genres, ['action', 'adventure', 'drama', 'comedy'], ['thriller'], ['flags' => ['size:md']]),
            'select__multiple_lg' => $multi('Multiple chips lg', $genres, ['action', 'adventure', 'drama', 'comedy'], ['action', 'horror'], ['flags' => ['size:lg']]),
            'select__rich_sm' => $single('Rich sm', $rich, 'pro', 'team', ['flags' => ['size:sm', 'rich']]),
            'select__rich_md' => $single('Rich md', $rich, 'pro', 'free', ['flags' => ['size:md', 'rich']]),
            'select__rich_lg' => $single('Rich lg', $rich, 'pro', 'team', ['flags' => ['size:lg', 'rich']]),
            'select__bordered' => $single('Bordered', $status, 'published', 'draft', ['flags' => ['variant:bordered']]),
            'select__flat' => $single('Flat', $status, 'published', 'draft', ['flags' => ['variant:flat']]),
            'select__soft' => $single('Soft', $status, 'published', 'draft', ['flags' => ['variant:soft']]),
            'select__faded' => $single('Faded', $status, 'published', 'draft', ['flags' => ['variant:faded']]),
            'select__underlined' => $single('Underlined', $status, 'published', 'draft', ['flags' => ['variant:underlined']]),
            'select__secondary' => $single('Secondary', $status, 'published', 'draft', ['flags' => ['variant:secondary']]),
            'select__item_card' => $single('Item card', $status, 'published', 'draft', ['flags' => ['variant:item-card']]),
            'select__inline_search' => $single('Inline search', $tech, 'tailwind', 'laravel', ['searchable' => true, 'flags' => ['inline_search']]),
            'select__entity_mentions' => $multi('Entity mentions', $people, ['jane', 'john'], ['fred'], ['flags' => ['entity_mentions']]),
            'select__inline_field_label' => $single('Inline field label', $status, 'published', 'draft', ['flags' => ['inline_field_label']]),
            'select__clearable' => $single('Clearable default', $status, 'published', 'draft', ['clearable' => true, 'flags' => ['clearable']]),
            'select__not_clearable' => $single('Not clearable', $status, 'published', 'draft', ['clearable' => false, 'flags' => ['not_clearable']]),
            'select__selectable_placeholder_false' => $single('Placeholder not re-selectable', $status, 'published', 'draft', ['flags' => ['selectable_placeholder_false']]),
            'select__domain_affix' => $single('Domain affixes', $domains, 'acme', 'flex', ['searchable' => true, 'flags' => ['affix']]),
            'select__prefix_icon' => $single('Prefix icon', $tech, 'tailwind', 'alpine', ['searchable' => true, 'flags' => ['prefix_icon']]),
            'select__dropdown_align_start' => $single('Dropdown align start', $status, 'published', 'draft', ['searchable' => true, 'flags' => ['align:start']]),
            'select__dropdown_align_end' => $single('Dropdown align end', $status, 'published', 'draft', ['searchable' => true, 'flags' => ['align:end']]),
            'select__color' => $single('Accent color', $status, 'published', 'draft', ['flags' => ['color']]),
            'select__rounding_full' => $single('Full rounding', $status, 'published', 'draft', ['flags' => ['rounding:full']]),
            'select__focus_outline' => $single('Focus outline', $status, 'published', 'draft', ['flags' => ['focus_outline']]),
            'select__chip_primary' => $multi('Chips primary', $genres, ['action', 'comedy'], ['drama'], ['flags' => ['chip:primary']]),
            'select__chip_success' => $multi('Chips success', $genres, ['adventure', 'drama'], ['horror'], ['flags' => ['chip:success']]),
            'select__chip_danger' => $multi('Chips danger', $genres, ['horror', 'thriller'], ['action'], ['flags' => ['chip:danger']]),
            'select__custom_trigger_icons' => $single('Custom trigger icons', $status, 'published', 'draft', ['flags' => ['custom_icons']]),
            'select__custom_check_icon' => $multi('Custom check icon', $states, ['california', 'texas'], ['delaware'], ['flags' => ['checklist', 'custom_check']]),
            'select__create_single' => $single('Create option single', $tech, 'laravel', 'svelte', ['searchable' => true, 'flags' => ['allow_create']]),
            'select__create_multiple' => $multi('Create option multiple', $tech, ['laravel'], ['laravel', 'svelte'], ['flags' => ['allow_create', 'min_max']]),
            'select__create_with_sections' => $single('Create + recent suggested', $tech, 'tailwind', 'filament', ['searchable' => true, 'flags' => ['allow_create', 'recent', 'suggested']]),
            'select__scale_10k' => $single('10k virtualized', ['opt_0' => 'Option 0', 'opt_1' => 'Option 1', 'opt_99' => 'Option 99', 'opt_999' => 'Option 999'], 'opt_0', 'opt_99', ['searchable' => true, 'flags' => ['virtualized']]),
            'select__cascade_country' => $single('Cascade country', ['us' => 'United States', 'pl' => 'Poland', 'ae' => 'UAE'], 'us', 'pl', ['live' => true, 'searchable' => true, 'flags' => ['live', 'cascade_parent']]),
            'select__cascade_region' => $single('Cascade region', ['ca' => 'California', 'tx' => 'Texas', 'mz' => 'Mazowieckie'], 'ca', 'tx', ['searchable' => true, 'flags' => ['depends_on', 'cascade_child']]),
            'select__rtl' => $single('RTL dropdown search', $citiesAr, 'riyadh', 'jeddah', ['searchable' => true, 'clearable' => true, 'flags' => ['rtl']]),
            'select__rtl_inline' => $single('RTL inline search', $techAr, 'laravel', 'tailwind', ['searchable' => true, 'clearable' => true, 'flags' => ['rtl', 'inline_search']]),
            'select__rtl_inline_field_label' => $single('RTL inline + label', $citiesAr, 'riyadh', 'dammam', ['searchable' => true, 'clearable' => true, 'flags' => ['rtl', 'inline_search', 'inline_field_label']]),
            'select__rtl_hebrew_inline' => $single('RTL Hebrew inline', $citiesHe, 'tel_aviv', 'haifa', ['searchable' => true, 'clearable' => true, 'flags' => ['rtl', 'inline_search', 'he']]),
            'select__rtl_dropdown_clearable' => $single('RTL dropdown + prefix', $citiesAr, 'jeddah', 'riyadh', ['searchable' => true, 'clearable' => true, 'flags' => ['rtl', 'prefix_icon', 'align:end']]),
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return Variant
     */
    public static function get(string $key): array
    {
        $all = self::all();

        if (! isset($all[$key])) {
            throw new \InvalidArgumentException("Unknown select playground variant [{$key}].");
        }

        return $all[$key];
    }

    /**
     * Atomic scenario ids per variant (~8k Livewire cases across all keys).
     *
     * Not part of default CI. Manual one-shot only:
     * `FFF_SELECT_ATOMICS_FULL=1 composer test:select-atomics`
     *
     * @return list<string>
     */
    public static function scenarioIds(string $key): array
    {
        $variant = self::get($key);
        $ids = [];

        // Core wire lifecycle (repeated to stress empty persistence)
        for ($i = 0; $i < 20; $i++) {
            $ids[] = "lifecycle_filled_{$i}";
            $ids[] = "lifecycle_clear_empty_{$i}";
            $ids[] = "lifecycle_refill_after_clear_{$i}";
        }

        // Every option key as a selection target
        foreach (array_keys($variant['options']) as $option) {
            $ids[] = "select_option_{$option}";
            $ids[] = "clear_from_option_{$option}";
            $ids[] = "db_persist_option_{$option}";
            $ids[] = "db_clear_empty_from_{$option}";
            $ids[] = "store_persist_option_{$option}";
            $ids[] = "store_clear_empty_from_{$option}";
        }

        // Ordered pair replaces
        $optionKeys = array_map(strval(...), array_keys($variant['options']));
        $pairBudget = 0;

        foreach ($optionKeys as $from) {
            foreach ($optionKeys as $to) {
                if ($from === $to) {
                    continue;
                }

                $ids[] = "replace_{$from}_to_{$to}";
                $pairBudget++;

                if ($pairBudget >= 24) {
                    break 2;
                }
            }
        }

        // Conflict hammers
        for ($i = 0; $i < 15; $i++) {
            $ids[] = "conflict_hammer_{$i}";
        }

        // Live + clear interaction
        for ($i = 0; $i < 10; $i++) {
            $ids[] = "live_clear_cycle_{$i}";
        }

        if ($variant['multiple']) {
            for ($i = 0; $i < 12; $i++) {
                $ids[] = "multi_reorder_{$i}";
                $ids[] = "multi_partial_then_clear_{$i}";
            }
        }

        if (in_array('depends_on', $variant['flags'] ?? [], true) || in_array('cascade_parent', $variant['flags'] ?? [], true)) {
            for ($i = 0; $i < 20; $i++) {
                $ids[] = "cascade_clear_{$i}";
            }
        }

        return $ids;
    }

    public static function wantsFullAtomicsMatrix(): bool
    {
        $raw = getenv('FFF_SELECT_ATOMICS_FULL');

        if ($raw === false || $raw === '') {
            return false;
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function dataset(): array
    {
        if (! self::wantsFullAtomicsMatrix()) {
            // Non-empty placeholder so Pest does not reject an empty provider when the file is run without the env flag.
            return ['manual_only' => ['__manual__', '__manual__']];
        }

        $rows = [];

        foreach (self::keys() as $key) {
            foreach (self::scenarioIds($key) as $scenario) {
                $rows["{$key}::{$scenario}"] = [$key, $scenario];
            }
        }

        return $rows;
    }
}
