<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schema\Admin;

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Schema\Admin\Concerns\BuildsCommonAdminFields;
use Bjanczak\FilamentFlexFields\Filament\Schema\Admin\Concerns\InteractsWithFieldTypeAdminContext;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldTypeSettingsStorage;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Str;

final class FieldTypeAutoAdminSchema
{
    use BuildsCommonAdminFields;
    use InteractsWithFieldTypeAdminContext;

    /**
     * @return list<string>
     */
    public static function configurableKeysFor(FieldType $type): array
    {
        $reserved = FlexFieldTypeSettingsStorage::reservedConfigKeys($type);

        return array_values(array_diff(array_keys($type->defaultConfig()), $reserved));
    }

    public static function hasConfigurableSettings(FieldType $type): bool
    {
        return self::configurableKeysFor($type) !== [];
    }

    /**
     * @return list<Component>
     */
    public static function schemaComponentsFor(FieldType $type): array
    {
        return self::schemaForType($type);
    }

    /**
     * @var array<string, array<string, string>>
     */
    private const ENUM_OPTIONS = [
        'size' => [
            'xs' => 'XS',
            'sm' => 'SM',
            'md' => 'MD',
            'lg' => 'LG',
            'xl' => 'XL',
        ],
        'variant' => [
            'default' => 'Default',
            'primary' => 'Primary',
            'bordered' => 'Bordered',
            'midnight' => 'Midnight',
        ],
        'input_variant' => [
            'default' => 'Default',
            'primary' => 'Primary',
            'bordered' => 'Bordered',
        ],
        'layout' => [
            'row' => 'Row',
            'card' => 'Card',
            'stack' => 'Stack',
            'grid' => 'Grid',
            'input' => 'Input',
            'map' => 'Map',
            'simple' => 'Simple',
            'advanced' => 'Advanced',
        ],
        'indicator' => [
            'radio' => 'Radio',
            'checkbox' => 'Checkbox',
            'none' => 'None',
        ],
        'mode' => [
            'radio' => 'Single choice',
            'checkbox' => 'Multiple choice',
        ],
        'label_position' => [
            'start' => 'Start',
            'end' => 'End',
        ],
        'image_fit' => [
            'cover' => 'Cover',
            'contain' => 'Contain',
        ],
        'rounding' => [
            'none' => 'None',
            'md' => 'MD',
            'lg' => 'LG',
            'full' => 'Full',
        ],
        'granularity' => [
            'day' => 'Day',
            'hour' => 'Hour',
            'minute' => 'Minute',
            'second' => 'Second',
        ],
        'time_picker' => [
            'segmented' => 'Segmented',
            'native' => 'Native',
        ],
        'store_format' => [
            'structured' => 'Structured',
            'string' => 'String',
        ],
        'format' => [
            'hex' => 'Hex',
            'rgb' => 'RGB',
            'hsl' => 'HSL',
        ],
        'allowed_characters' => [
            'numeric' => 'Numeric',
            'alpha' => 'Alpha',
            'alphanumeric' => 'Alphanumeric',
        ],
        'mask_preset' => [
            '' => 'None',
            'phone' => 'Phone',
            'custom' => 'Custom',
        ],
        'search_scope' => [
            'all' => 'All',
            'country' => 'Country',
            'city' => 'City',
        ],
        'search_area' => [
            'global' => 'Global',
            'local' => 'Local',
        ],
        'language_mode' => [
            'auto' => 'Auto',
            'fixed' => 'Fixed',
        ],
        'controls_layout' => [
            'compact' => 'Compact',
            'expanded' => 'Expanded',
        ],
        'camera_facing' => [
            'environment' => 'Rear',
            'user' => 'Front',
        ],
        'value_position' => [
            'start' => 'Start',
            'end' => 'End',
        ],
        'color' => [
            'primary' => 'Primary',
            'success' => 'Success',
            'warning' => 'Warning',
            'danger' => 'Danger',
            'neutral' => 'Neutral',
        ],
        'chip_color' => [
            'neutral' => 'Neutral',
            'primary' => 'Primary',
            'success' => 'Success',
            'warning' => 'Warning',
            'danger' => 'Danger',
        ],
        'download_format' => [
            'svg' => 'SVG',
            'webp' => 'WebP',
        ],
        'month_display' => [
            'numeric' => 'Numeric',
            'short' => 'Short',
            'long' => 'Long',
        ],
        'dropdown_align' => [
            'start' => 'Start',
            'center' => 'Center',
            'end' => 'End',
        ],
        'search_results_layout' => [
            'grid' => 'Grid',
            'list' => 'List',
        ],
        'auto_submit_method' => [
            'livewire' => 'Livewire',
            'form' => 'Form',
        ],
    ];

    /**
     * @var list<string>
     */
    private const TAGS_ARRAY_KEYS = [
        'colors',
        'countries',
        'except_countries',
        'timezones',
        'except_timezones',
        'fields',
        'required_fields',
        'platforms',
        'exclude_platforms',
        'formats',
        'disabled_options',
        'disabled_rows',
        'required_rows',
        'locked_days',
        'workdays',
        'days',
        'currencies',
        'grid_colors',
        'split_keys',
        'labels',
        'locked_segments',
        'search_types',
        'icons',
        'descriptions',
        'histogram',
        'accepted_types',
        'sets',
        'exclude_icons',
        'unavailable_dates',
        'playback_rates',
        'quality_options',
        'disabled_options',
    ];

    /**
     * @var list<string>
     */
    private const COLOR_KEYS = [
        'pen_color',
        'background_color',
        'suffix_icon_color',
        'on_color',
        'off_color',
        'fill_color',
    ];

    /**
     * @var list<string>
     */
    private const JSON_ARRAY_KEYS = [
        'disable_cell_when',
        'disable_row_when',
        'column_icons',
        'options',
        'submit_action',
        'toolbar_selects',
        'toolbar_select',
        'slug_unique_parameters',
        'custom_properties',
    ];

    /**
     * @return list<Component>
     */
    public static function components(): array
    {
        $components = [];

        foreach (FieldType::cases() as $type) {
            $schema = self::schemaForType($type);

            if ($schema === []) {
                continue;
            }

            $components[] = Fieldset::make('type_settings_'.$type->value)
                ->label(__('filament-flex-fields::default.schema.type_settings_for', [
                    'type' => Str::headline(str_replace('_', ' ', $type->value)),
                ]))
                ->schema($schema)
                ->columns(3)
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => self::selectedType($get) === $type);
        }

        return $components;
    }

    /**
     * @return list<Component>
     */
    private static function schemaForType(FieldType $type): array
    {
        $defaults = $type->defaultConfig();
        $reserved = FlexFieldTypeSettingsStorage::reservedConfigKeys($type);
        $schema = [];

        foreach ($defaults as $key => $default) {
            if (in_array($key, $reserved, true)) {
                continue;
            }

            $schema[] = self::componentForKey($key, $default);
        }

        return $schema;
    }

    private static function componentForKey(string $key, mixed $default): Component
    {
        $path = self::settingsPath($key);
        $label = Str::headline(str_replace('_', ' ', $key));

        if (in_array($key, self::COLOR_KEYS, true)) {
            return ColorPicker::make($path)
                ->label($label)
                ->hexColor();
        }

        if (isset(self::ENUM_OPTIONS[$key])) {
            return Select::make($path)
                ->label($label)
                ->options(self::ENUM_OPTIONS[$key])
                ->native(false)
                ->searchable();
        }

        if (in_array($key, self::TAGS_ARRAY_KEYS, true) || (is_array($default) && array_is_list($default) && ($default === [] || is_string($default[0] ?? null)))) {
            return TagsInput::make($path)
                ->label($label)
                ->placeholder($label);
        }

        if (in_array($key, self::JSON_ARRAY_KEYS, true) || (is_array($default) && ! array_is_list($default))) {
            return Textarea::make($path)
                ->label($label)
                ->rows(3)
                ->helperText(__('filament-flex-fields::default.schema.settings.structured_value_help'))
                ->formatStateUsing(function (mixed $state): ?string {
                    if ($state === null || $state === '') {
                        return null;
                    }

                    if (is_array($state)) {
                        return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: null;
                    }

                    return is_string($state) ? $state : null;
                })
                ->dehydrateStateUsing(function (mixed $state) use ($default): mixed {
                    if (! is_string($state) || trim($state) === '') {
                        return is_array($default) ? $default : null;
                    }

                    $decoded = json_decode($state, true);

                    return is_array($decoded) ? $decoded : $state;
                });
        }

        if (is_bool($default)) {
            return Toggle::make($path)
                ->label($label)
                ->inline(false);
        }

        if (is_int($default) || is_float($default)) {
            return TextInput::make($path)
                ->label($label)
                ->numeric();
        }

        if (is_array($default)) {
            return TagsInput::make($path)
                ->label($label);
        }

        return TextInput::make($path)
            ->label($label);
    }

    /**
     * @return list<string>
     */
    public static function coveredSettingKeys(): array
    {
        $keys = [];

        foreach (FieldType::cases() as $type) {
            $reserved = FlexFieldTypeSettingsStorage::reservedConfigKeys($type);

            foreach (array_keys($type->defaultConfig()) as $key) {
                if (! in_array($key, $reserved, true)) {
                    $keys[] = $key;
                }
            }
        }

        return array_values(array_unique($keys));
    }
}
