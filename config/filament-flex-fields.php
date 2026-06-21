<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Plugin Enabled
    |--------------------------------------------------------------------------
    */
    'enabled' => env('FLEX_FIELDS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Media URL security (VideoField, AudioField)
    |--------------------------------------------------------------------------
    */
    'security' => [
        'allow_http_media' => env('FLEX_FIELDS_ALLOW_HTTP_MEDIA', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON Storage Column
    |--------------------------------------------------------------------------
    | Column on your Eloquent model where all flex field values are stored.
    | No per-field migrations — add this single JSON column once per model.
    */
    'values_column' => env('FLEX_FIELDS_VALUES_COLUMN', 'flex_field_values'),

    /*
    |--------------------------------------------------------------------------
    | Flex field audit trail
    |--------------------------------------------------------------------------
    | When enabled, value changes are appended to a JSON audit column.
    */
    'audit' => [
        'enabled' => env('FLEX_FIELDS_AUDIT_ENABLED', true),
        'column' => env('FLEX_FIELDS_AUDIT_COLUMN', 'flex_field_audit'),
        'max_entries' => (int) env('FLEX_FIELDS_AUDIT_MAX_ENTRIES', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Schemas (no database)
    |--------------------------------------------------------------------------
    | Define form fields in PHP config or register schemas programmatically
    | via FlexFieldSchemaRegistry in a service provider.
    |
    | Example:
    | 'user_profile' => [
    |     'version' => 1,
    |     'target' => App\Models\User::class,
    |     'label' => 'User profile',
    |     'fields' => [
    |         [
    |             'slug' => 'bio',
    |             'label' => 'Bio',
    |             'type' => 'multi_line_text',
    |             'is_required' => false,
    |         ],
    |     ],
    | ],
    */
    'schemas' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Playground (dev UI for previewing all field types)
    |--------------------------------------------------------------------------
    */
    'playground' => [
        'enabled' => env('FLEX_FIELDS_PLAYGROUND', env('APP_ENV') === 'local'),
        'navigation_group' => env('FLEX_FIELDS_PLAYGROUND_NAV_GROUP', 'Settings & Tools'),
        'navigation_sort' => (int) env('FLEX_FIELDS_PLAYGROUND_NAV_SORT', 91),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mapbox (MapPicker & AddressAutocomplete fields)
    |--------------------------------------------------------------------------
    */
    'mapbox' => [
        'access_token' => env('MAPBOX_ACCESS_TOKEN'),
        'use_server_proxy' => env('FLEX_FIELDS_MAPBOX_SERVER_PROXY', true),
        'default_language' => env('FLEX_FIELDS_MAPBOX_LANGUAGE', null),
        'cache_ttl_seconds' => (int) env('FLEX_FIELDS_MAPBOX_CACHE_TTL', 3600),
        'rate_limit_per_minute' => (int) env('FLEX_FIELDS_MAPBOX_RATE_LIMIT', 60),
        'proxy_prefix' => 'flex-fields',
        'proxy_middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Link preview (LinkPreviewField)
    |--------------------------------------------------------------------------
    */
    'link_preview' => [
        'cache_ttl_seconds' => (int) env('FLEX_FIELDS_LINK_PREVIEW_CACHE_TTL', 86_400),
        'rate_limit_per_minute' => (int) env('FLEX_FIELDS_LINK_PREVIEW_RATE_LIMIT', 30),
        'timeout_seconds' => (int) env('FLEX_FIELDS_LINK_PREVIEW_TIMEOUT', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | CurrencyField — extra / override currencies
    |--------------------------------------------------------------------------
    | Merged on top of the built-in list in CurrencyCountries. Use ISO 4217 codes.
    | Matching keys override built-in metadata (symbol, decimals, locale).
    |
    | Optional translations: lang/vendor/filament-flex-fields/{locale}/currencies.php
    |
    | Example:
    | 'VND' => [
    |     'symbol' => '₫',
    |     'name' => 'Vietnamese dong',
    |     'decimals' => 0,
    |     'locale' => 'vi-VN',
    | ],
    */
    'currencies' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Slug / TitleSlugField defaults
    |--------------------------------------------------------------------------
    */
    'slug' => [
        'field_title' => 'title',
        'field_slug' => 'slug',
        'url_host' => env('APP_URL'),
        'action_button_labels' => true,
        /*
        | Optional translatable title locales for TitleSlugField::make().
        | Example: ['pl' => 'PL', 'en' => 'EN'] or ['pl', 'en'].
        | Leave null to keep a single-language title field.
        */
        'translatable_locales' => null,
        /*
        | Locale whose title is used to auto-generate the slug when translatable
        | titles are enabled. Defaults to app.locale or the first locale.
        */
        'slug_source_locale' => null,
        /*
        | When true, marks the field as intended for Spatie laravel-translatable models.
        | Runtime hydrate auto-detects HasTranslations on the record when the package
        | is installed. Without Spatie, JSON / array title columns still work.
        */
        'spatie_translatable' => false,
        /*
        | Which translatable title locales are required on save.
        | null = only slug_source_locale (or app locale) is required.
        | 'all' = every configured locale is required.
        | ['en'] = only the listed locales are required.
        */
        'required_title_locales' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | TranslatableFields defaults
    |--------------------------------------------------------------------------
    */
    'translatable' => [
        /*
        | Default locales for TranslatableFields::make() when ->locales() is omitted.
        | Example: ['ar' => 'Arabic', 'en' => 'English'] or ['ar', 'en'].
        */
        'locales' => null,
        /*
        | Labels keyed by locale code. Used when locales is a list: ['ar', 'en'].
        | Example: ['ar' => 'Arabic', 'en' => 'English'].
        */
        'locale_labels' => null,
        /*
        | Badge label shown on locale tabs where all fields are empty.
        */
        'empty_badge_label' => 'empty',
        /*
        | Locales that should render fields right-to-left in directionByLocale().
        */
        'rtl_locales' => ['ar', 'he', 'fa', 'ur'],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Defaults (modern SaaS-inspired cell components)
    |--------------------------------------------------------------------------
    | Control sizes (sm / md / lg) share one scale across all components.
    | Track heights: 32px · 40px · 48px — see --fff-track-* in base.css.
    */
    'ui' => [
        'cell_height' => 'md',
        'number_stepper_size' => 'md',
        /*
        | Default stepper button icons when ->decrementIcon() / ->incrementIcon() are not set.
        | Any Filament-supported icon works: gravityui-*, heroicon-o-*, etc.
        */
        'number_stepper_decrement_icon' => 'gravityui-minus',
        'number_stepper_increment_icon' => 'gravityui-plus',
        'segment_size' => 'md',
        'slider_size' => 'md',
        'switch_size' => 'md',
        'rating_size' => 'md',
        'select_size' => 'md',
        'select_variant' => 'bordered',
        'segment_variant' => 'default',
        'slider_variant' => 'default',
        'switch_variant' => 'default',
        'credit_card_size' => 'md',
        'credit_card_variant' => 'midnight',
        'flex_text_input_size' => 'md',
        'flex_text_input_variant' => 'primary',
        'slug_size' => 'md',
        'slug_variant' => 'primary',
        'price_range_size' => 'md',
        'price_range_variant' => 'primary',
        /*
        | Default FlexTextInput built-in action icons.
        */
        'flex_text_input_copy_icon' => 'gravityui-copy',
        'flex_text_input_show_password_icon' => 'gravityui-eye',
        'flex_text_input_hide_password_icon' => 'gravityui-eye-closed',
        'flex_text_input_emoji_icon' => 'gravityui-face-smile',
        'flex_text_input_microphone_icon' => 'gravityui-microphone',
        'flex_textarea_emoji_icon' => 'gravityui-face-smile',
        'flex_textarea_microphone_icon' => 'gravityui-microphone',
        /*
        | Default FlexRichEditor toolbar icons (override any key from RichEditorGravityIcons::icon()).
        */
        'flex_rich_editor_bold_icon' => 'gravityui-bold',
        'flex_rich_editor_italic_icon' => 'gravityui-italic',
        'flex_rich_editor_link_icon' => 'gravityui-link',
        'flex_rich_editor_clear_formatting_icon' => 'gravityui-eraser',
        'flex_rich_editor_clear_content_icon' => 'gravityui-trash-bin',
        'phone_size' => 'md',
        'phone_variant' => 'primary',
        'phone_default_country' => 'PL',
        'currency_size' => 'md',
        'currency_variant' => 'primary',
        /*
        | Default suffix icon for PhoneField when ->suffixIcon() is not set.
        | Any Filament-supported icon works: gravityui-*, heroicon-o-*, ri-*, etc.
        */
        'phone_suffix_icon' => 'gravityui-smartphone',
        'country_size' => 'md',
        'country_variant' => 'primary',
        'country_default_country' => 'PL',
        /*
        | Default trigger icons for SelectField when ->chevronIcon() / ->clearIcon() are not set.
        | Any Filament-supported icon works: gravityui-*, heroicon-o-*, ri-*, etc.
        */
        'select_chevron_icon' => 'gravityui-circle-chevron-down',
        'select_clear_icon' => 'gravityui-circle-xmark',
        'select_selected_option_check_icon' => 'gravityui-check',
        'address_autocomplete_size' => 'md',
        'address_autocomplete_variant' => 'primary',
        'address_autocomplete_prefix_icon' => 'gravityui-map-pin',
        'address_autocomplete_clear_icon' => 'gravityui-circle-xmark',
        /*
        | Default DualListboxField icons when ->icons() / individual icon methods are not set.
        | Any Filament-supported icon works: gravityui-*, heroicon-o-*, ri-*, etc.
        */
        'dual_listbox_search_icon' => 'gravityui-magnifier',
        'dual_listbox_move_all_right_icon' => 'gravityui-arrow-chevron-right',
        'dual_listbox_move_right_icon' => 'gravityui-arrow-right',
        'dual_listbox_swap_icon' => 'gravityui-arrow-right-arrow-left',
        'dual_listbox_move_left_icon' => 'gravityui-arrow-left',
        'dual_listbox_move_all_left_icon' => 'gravityui-arrow-chevron-left',
        'dual_listbox_move_up_icon' => 'gravityui-chevron-up',
        'dual_listbox_move_down_icon' => 'gravityui-chevron-down',
        /*
        | Default ColorSwatchField section header icon when ->sectionLabel() is set
        | and ->sectionIcon() is not overridden.
        */
        'color_swatch_section_icon' => 'gravityui-palette',
        /*
        | Default VideoField control icons.
        */
        'video_play_icon' => 'gravityui-play-fill',
        'video_pause_icon' => 'gravityui-pause-fill',
        'video_volume_icon' => 'gravityui-volume-fill',
        'video_mute_icon' => 'gravityui-volume-slash-fill',
        'video_fullscreen_icon' => 'gravityui-chevrons-expand-up-right',
        'video_exit_fullscreen_icon' => 'gravityui-chevrons-collapse-up-right',
        'video_picture_in_picture_icon' => 'gravityui-copy-picture',
        'video_exit_picture_in_picture_icon' => 'gravityui-chevrons-collapse-up-right',
        'video_placeholder_icon' => 'gravityui-video',
        'video_auto_hide_controls' => true,
        'video_controls_layout' => 'default',
        /*
        | Default FlexChecklist / FlexRadiolist / ItemCard icons.
        */
        'flex_checklist_lock_icon' => 'gravityui-lock',
        'flex_radiolist_lock_icon' => 'gravityui-lock',
        'item_card_chevron_icon' => 'gravityui-chevron-right',
        /*
        | Default AudioField control icons.
        */
        'audio_play_icon' => 'gravityui-play-fill',
        'audio_pause_icon' => 'gravityui-pause-fill',
        /*
        | Default SignatureField control icons.
        */
        'signature_undo_icon' => 'gravityui-arrow-rotate-left',
        'signature_clear_icon' => 'gravityui-arrows-rotate-right',
        'signature_download_icon' => 'gravityui-arrow-down-to-square',
        'signature_fullscreen_icon' => 'gravityui-chevrons-expand-up-right',
        'signature_close_icon' => 'gravityui-xmark',
        'video_size' => 'md',
        'audio_size' => 'md',
        /*
        | Default icon sets for IconPickerField when ->sets() is not called.
        | null = all installed blade-icons sets. Example: ['heroicons', 'gravity-icons']
        */
        'icon_picker_sets' => null,
        'icon_picker_size' => 'md',
        'icon_picker_variant' => 'bordered',
        'icon_picker_index_cache_days' => 7,
        'icon_picker_svg_cache_days' => 30,
        'icon_picker_catalog_cache_days' => 7,
        'icon_picker_search_cache_minutes' => 60,
        'icon_picker_use_bundled_manifest' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | FlexRichEditor
    |--------------------------------------------------------------------------
    */
    'rich_editor' => [
        'reading_time_words_per_minute' => 200,
        'toolbar_roles' => [
            'author' => [
                ['bold', 'italic', 'underline'],
                ['link', 'attachFiles'],
            ],
            'editor' => [
                ['undo', 'redo'],
                ['bold', 'italic', 'underline', 'strike'],
                ['link', 'attachFiles'],
                ['bulletList', 'orderedList'],
            ],
            'admin' => [
                ['undo', 'redo'],
                ['bold', 'italic', 'underline', 'strike', 'code'],
                ['h1', 'h2', 'h3'],
                ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                ['blockquote', 'codeBlock'],
                ['bulletList', 'orderedList'],
                ['link', 'attachFiles'],
                ['clearFormatting', 'clearContent'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation presets
    |--------------------------------------------------------------------------
    */
    'validation_presets' => [
        'required',
        'nullable',
        'email',
        'url',
        'numeric',
        'integer',
        'min',
        'max',
        'regex',
        'unique',
    ],

];
