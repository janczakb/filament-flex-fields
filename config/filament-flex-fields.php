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
    | AudioField — client-side Whisper transcription (optional)
    |--------------------------------------------------------------------------
    |
    | Powers AudioField::transcription() using @xenova/transformers in-browser.
    | Models download from Hugging Face on first "Transcribe Audio" click.
    |
    */
    'audio' => [
        'transcription' => [
            'default_model' => env('FLEX_FIELDS_WHISPER_MODEL', 'Xenova/whisper-tiny'),
            'default_quantized' => env('FLEX_FIELDS_WHISPER_QUANTIZED', true),
            'default_multilingual' => env('FLEX_FIELDS_WHISPER_MULTILINGUAL', true),
            'default_language' => env('FLEX_FIELDS_WHISPER_LANGUAGE'),
            'default_task' => env('FLEX_FIELDS_WHISPER_TASK', 'transcribe'),
            'languages' => [],
        ],
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
    | Schema product (M8)
    |--------------------------------------------------------------------------
    | Import/export, blueprint packs, and optional Filament admin CRUD for
    | FlexFieldGroup (name, slug, fields JSON, order, tenant_id).
    | Default false — enable explicitly so hosts do not get surprise admin pages.
    |
    | Enable:
    |   FLEX_FIELDS_SCHEMA_RESOURCE_ENABLED=true
    |   php artisan vendor:publish --tag=filament-flex-fields-migrations
    |   php artisan migrate
    |
    | See docs/flex-field-groups.md
    */
    'schema' => [
        'resource_enabled' => env('FLEX_FIELDS_SCHEMA_RESOURCE_ENABLED', false),
        'navigation_group' => env('FLEX_FIELDS_SCHEMA_NAV_GROUP', 'Flex Fields'),
        'navigation_sort' => (int) env('FLEX_FIELDS_SCHEMA_NAV_SORT', 90),
        /*
         * Persist SchemaRegistry publish/rollback history in
         * `flex_field_schema_versions` when the table exists. Falls back to
         * in-memory storage when the migration has not been run or this is false.
         */
        'registry_persistence' => env('FLEX_FIELDS_SCHEMA_REGISTRY_DB', true),
        /*
         * Keep FlexFieldSchemaRegistry aligned with flex_field_groups rows.
         * sync_from_database: hydrate registry on app boot (after config schemas).
         * sync_on_save: update registry when groups are saved/deleted in admin or code.
         */
        'sync_from_database' => env('FLEX_FIELDS_SCHEMA_SYNC_FROM_DB', true),
        'sync_on_save' => env('FLEX_FIELDS_SCHEMA_SYNC_ON_SAVE', true),
        'default_target_type' => env('FLEX_FIELDS_SCHEMA_DEFAULT_TARGET', 'App\\Models\\Model'),
        /*
         * Gate ability required for FlexFieldGroupResource CRUD + publish/rollback.
         * Define in AppServiceProvider: Gate::define('manageFlexFieldSchemas', ...).
         */
        'policy_ability' => env('FLEX_FIELDS_SCHEMA_POLICY_ABILITY', 'manageFlexFieldSchemas'),
        /*
         * Optional callable (class@method or closure) registered in AppServiceProvider
         * to resolve the active tenant id for schema filtering and admin scoping.
         * Signature: fn (?object $context): ?string
         */
        'tenant_resolver' => null,
        /*
         * Optional callable to resolve RBAC user key for FieldRbacMatrix checks.
         * Signature: fn (?object $context): ?string
         */
        'rbac_user_key_resolver' => null,
        /*
         * When true, FlexFieldGroupResource list query is filtered to the resolved tenant.
         */
        'scope_resource_by_tenant' => env('FLEX_FIELDS_SCHEMA_SCOPE_BY_TENANT', false),
        /*
         * Hub page with entity tabs listing field groups (Flex Field Studio).
         */
        'management_page_enabled' => env('FLEX_FIELDS_SCHEMA_MANAGEMENT_PAGE', true),
        'management_navigation_sort' => (int) env('FLEX_FIELDS_SCHEMA_MANAGEMENT_NAV_SORT', 89),
        /*
         * Entity discovery for the management hub and target_type pickers.
         */
        'entity_discovery' => [
            'from_filament_resources' => env('FLEX_FIELDS_ENTITY_DISCOVERY_RESOURCES', true),
            'paths' => [],
            'namespace' => null,
        ],
        'entities' => [
            // 'App\\Models\\Lead' => ['label' => 'Leads', 'icon' => 'heroicon-o-user', 'sort' => 0],
        ],
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
    | Geocoding provider OS (AddressAutocomplete / MapPicker proxy)
    |--------------------------------------------------------------------------
    */
    'geocoding' => [
        'driver' => env('FLEX_FIELDS_GEOCODING_DRIVER', 'mapbox'),
        'circuit_breaker' => [
            'enabled' => env('FLEX_FIELDS_GEOCODING_CIRCUIT_BREAKER', true),
            'failure_threshold' => (int) env('FLEX_FIELDS_GEOCODING_CB_THRESHOLD', 5),
            'open_seconds' => (int) env('FLEX_FIELDS_GEOCODING_CB_OPEN_SECONDS', 60),
        ],
        'rate_limit_per_minute' => (int) env('FLEX_FIELDS_GEOCODING_RATE_LIMIT', env('FLEX_FIELDS_MAPBOX_RATE_LIMIT', 60)),
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
        | Locales that should render right-to-left in directionByLocale().
        | Primary subtags match (e.g. ar-SA → ar). Default applies dir to inputs when supported.
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
        /*
        | Global density scale for all Flex Fields (compact / comfortable / spacious).
        | Maps to --fff-density-scale on the document root via data-fff-density.
        */
        'density' => 'comfortable',
        /*
        | Optional theme overrides (primary color, radius, etc.) merged into CSS variables.
        | Keys: primary, radius, menu_radius, field_bg, field_border — or --fff-* names.
        */
        'theme' => [],
        /*
        | Defines the rounding of the fields.
        | Options:
        | - 'native': Matches Filament's native fields perfectly (0.5rem)
        | - 'md': The package's default rounding for flat SaaS design (0.75rem)
        | - 'lg': Slightly larger rounding (1rem)
        | - 'xl': Large rounding (1.25rem)
        | - 'full': Fully rounded / pill shape (9999px)
        |
        | (Using 'default' acts as an alias for 'md').
        */
        'field_rounding' => 'default',
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
        'signature_pdf_preview_icon' => 'gravityui-file-text',
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

    /*
    |--------------------------------------------------------------------------
    | v3 platform upgrade
    |--------------------------------------------------------------------------
    */
    'v3' => [
        'auto_upgrade' => env('FLEX_FIELDS_V3_AUTO_UPGRADE', true),
        'migrated' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | SelectField engine (v3 headless combobox migration)
    |--------------------------------------------------------------------------
    */
    'select' => [
        // Runtime always uses headless for eligible fields; keys kept for upgrade tooling/tests.
        // Headless combobox is the default runtime for eligible SelectField instances.
        'use_headless_engine' => env('FLEX_FIELDS_SELECT_USE_HEADLESS_ENGINE', true),
        'auto_migrate' => env('FLEX_FIELDS_SELECT_AUTO_MIGRATE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Media & Capture OS (M5 enterprise)
    |--------------------------------------------------------------------------
    | Virus scan, signed URLs, PCI tokenization, voice-note transcription,
    | retention policies, and Spatie Media Library enterprise adapter hooks.
    |
    | Wire callbacks in a service provider:
    |   MediaCaptureOs::registerVirusScanCallback(fn ($path) => ...);
    |   MediaCaptureOs::registerSignedUploadUrlResolver(fn ($disk, $path, $ctx) => ...);
    |   MediaCaptureOs::registerTokenizeCreditCardCallback(fn ($pan) => ...);
    |
    | Or bind transcription:
    |   'transcription' => App\Media\VoiceNoteTranscriber::class,
    */
    'media_capture' => [
        'disk' => env('FLEX_FIELDS_MEDIA_DISK', env('FILESYSTEM_DISK', 'local')),
        'require_virus_scan' => env('FLEX_FIELDS_REQUIRE_VIRUS_SCAN', false),
        'scan_before_persist' => env('FLEX_FIELDS_SCAN_BEFORE_PERSIST', true),
        'quarantine_disk' => env('FLEX_FIELDS_QUARANTINE_DISK'),
        'auto_signed_urls' => env('FLEX_FIELDS_MEDIA_AUTO_SIGNED_URLS', true),
        'signed_url_minutes' => (int) env('FLEX_FIELDS_MEDIA_SIGNED_URL_MINUTES', 15),
        'transcription' => env('FLEX_FIELDS_MEDIA_TRANSCRIPTION'),
        'transcription_circuit_breaker' => [
            'enabled' => env('FLEX_FIELDS_TRANSCRIPTION_CIRCUIT_BREAKER', true),
            'failure_threshold' => (int) env('FLEX_FIELDS_TRANSCRIPTION_CB_THRESHOLD', 3),
            'open_seconds' => (int) env('FLEX_FIELDS_TRANSCRIPTION_CB_OPEN_SECONDS', 30),
        ],
        'pci' => [
            'never_store_pan' => env('FLEX_FIELDS_PCI_NEVER_STORE_PAN', true),
            'require_tokenization' => env('FLEX_FIELDS_PCI_REQUIRE_TOKENIZATION', false),
        ],
        'tenant' => [
            'disk' => env('FLEX_FIELDS_MEDIA_TENANT_DISK'),
            'directory_prefix' => env('FLEX_FIELDS_MEDIA_TENANT_DIRECTORY_PREFIX'),
            'auto_disk' => env('FLEX_FIELDS_MEDIA_TENANT_AUTO_DISK', false),
        ],
        'spatie' => [
            'prune_collections' => [],
        ],
        'directories' => [
            'temp_captures' => ['livewire-tmp'],
            'voice_notes' => ['voice-notes'],
            'uploads' => ['uploads'],
            'signatures' => ['signatures'],
        ],
        'retention' => [
            'schedule_enabled' => env('FLEX_FIELDS_RETENTION_SCHEDULE', true),
            'schedule' => env('FLEX_FIELDS_RETENTION_SCHEDULE_EXPRESSION', 'daily'),
            'uploads' => [
                'enabled' => env('FLEX_FIELDS_RETENTION_UPLOADS', false),
                'days' => env('FLEX_FIELDS_RETENTION_UPLOADS_DAYS'),
            ],
            'voice_notes' => [
                'enabled' => env('FLEX_FIELDS_RETENTION_VOICE_NOTES', false),
                'days' => (int) env('FLEX_FIELDS_RETENTION_VOICE_NOTES_DAYS', 365),
            ],
            'signatures' => [
                'enabled' => env('FLEX_FIELDS_RETENTION_SIGNATURES', false),
                'days' => env('FLEX_FIELDS_RETENTION_SIGNATURES_DAYS'),
            ],
            'temp_captures' => [
                'enabled' => env('FLEX_FIELDS_RETENTION_TEMP_CAPTURES', true),
                'days' => (int) env('FLEX_FIELDS_RETENTION_TEMP_CAPTURES_DAYS', 7),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Enterprise control plane (M13 foundations)
    |--------------------------------------------------------------------------
    | Optional kill-switch for tenant packs / RBAC / SchemaRegistry /
    | ObservabilityHooks product wiring. Default true — first install gets
    | full v3. Set false only for a slim install without TenantFieldPacks,
    | FieldRbacMatrix, SchemaRegistry, or ObservabilityHooks.
    */
    'enterprise' => [
        'enabled' => env('FLEX_FIELDS_ENTERPRISE_ENABLED', true),
        /*
         * SIEM / log forwarding for ObservabilityHooks events.
         * driver: null (off) | log (Laravel Log::channel)
         * Host apps can also call SiemBridge::registerSink(fn ($event, $envelope) => ...).
         */
        'siem' => [
            'driver' => env('FLEX_FIELDS_SIEM_DRIVER', 'null'),
            'channel' => env('FLEX_FIELDS_SIEM_CHANNEL', 'stack'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Intelligence (formulas)
    |--------------------------------------------------------------------------
    |
    | FormulaEngine is always available for FormBuilder `formula` /
    | `config.calculated` fields. The legacy `formulas` flag is ignored and
    | kept only so existing .env / config publishes do not break.
    |
    */
    'intelligence' => [
        'formulas' => true,
    ],

];
