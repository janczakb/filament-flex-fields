<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Schema\Admin\FieldTypeAutoAdminSchema;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldTypeSettingsStorage;

/**
 * @var array<string, list<string>> $typeToConfigurators
 */
$typeToConfigurators = [
    'flex_textarea' => ['FlexTextareaFieldConfigurator'],
    'flex_text_input' => ['FlexTextInputFieldConfigurator'],
    'credit_card' => ['CreditCardFieldConfigurator'],
    'phone' => ['PhoneFieldConfigurator'],
    'country' => ['CountryFieldConfigurator'],
    'timezone' => ['TimezoneFieldConfigurator'],
    'slug' => ['SlugFieldConfigurator'],
    'address_autocomplete' => ['AddressAutocompleteFieldConfigurator', 'AppliesGeocodingStudioConfig'],
    'verification_code' => ['FlexVerificationCodeFieldConfigurator'],
    'icon_picker' => ['IconPickerFieldConfigurator'],
    'email' => ['FlexTextInputFieldConfigurator'],
    'url' => ['FlexTextInputFieldConfigurator'],
    'integer' => ['FlexTextInputFieldConfigurator'],
    'decimal' => ['FlexTextInputFieldConfigurator'],
    'number_stepper' => ['NumberStepperFieldConfigurator'],
    'currency' => ['CurrencyFieldConfigurator'],
    'percentage' => ['TrackSliderFieldConfigurator'],
    'range_slider' => ['TrackSliderFieldConfigurator'],
    'range_min_max' => ['TrackSliderFieldConfigurator'],
    'flex_slider' => ['FlexSliderFieldConfigurator'],
    'price_range' => ['PriceRangeFieldConfigurator'],
    'traffic_split' => ['TrafficSplitFieldConfigurator'],
    'toggle' => ['SwitchFieldConfigurator'],
    'segment_control' => ['SegmentControlFieldConfigurator'],
    'choice_cards' => ['ChoiceCardsFieldConfigurator'],
    'choice_checkbox_cards' => ['ChoiceCheckboxCardsFieldConfigurator'],
    'image_choice_cards' => ['ImageChoiceCardsFieldConfigurator'],
    'flex_checklist' => ['FlexChecklistFieldConfigurator'],
    'flex_radiolist' => ['FlexRadiolistFieldConfigurator'],
    'matrix_choice' => ['MatrixChoiceFieldConfigurator'],
    'select' => ['SelectFieldConfigurator'],
    'user_select' => ['UserSelectFieldConfigurator'],
    'dual_listbox' => ['DualListboxFieldConfigurator'],
    'tags' => ['TagsFieldConfigurator'],
    'rating' => ['RatingFieldConfigurator'],
    'nps' => ['NpsFieldConfigurator'],
    'date' => ['DateTimeFieldConfigurator'],
    'time' => ['DateTimeFieldConfigurator'],
    'date_time' => ['DateTimeFieldConfigurator'],
    'date_range' => ['DateTimeFieldConfigurator'],
    'duration' => ['DateTimeFieldConfigurator'],
    'time_range' => ['DateTimeFieldConfigurator'],
    'month' => ['DateTimeFieldConfigurator'],
    'year' => ['DateTimeFieldConfigurator'],
    'schedule' => ['ScheduleFieldConfigurator'],
    'color' => ['ColorSwatchFieldConfigurator'],
    'color_presets' => ['ColorSwatchFieldConfigurator'],
    'flex_color_picker' => ['FlexColorPickerFieldConfigurator'],
    'file' => ['FlexFileUploadFieldConfigurator', 'FlexSpatieMediaLibraryFieldConfigurator'],
    'image' => ['FlexFileUploadFieldConfigurator', 'FlexSpatieMediaLibraryFieldConfigurator'],
    'video' => ['VideoFieldConfigurator'],
    'audio' => ['AudioFieldConfigurator'],
    'voice_note' => ['VoiceNoteRecorderFieldConfigurator'],
    'map_picker' => ['MapPickerFieldConfigurator', 'AppliesGeocodingStudioConfig'],
    'social_links' => ['SocialLinksFieldConfigurator'],
    'signature' => ['SignatureFieldConfigurator'],
    'barcode' => ['BarcodeScannerFieldConfigurator'],
];

$handlerOnlyKeys = [
    'slug' => ['title_slug', 'title_field', 'required_title_locales'],
    'file' => ['use_spatie_media_library', 'directory'],
    'image' => ['use_spatie_media_library', 'directory'],
    'integer' => ['allow_decimals'],
];

$skipKeys = ['options', 'rows', 'columns', 'model', 'option_model', 'desc', 'min_date', 'max_date', 'grid_columns', 'cast', 'airplay'];

function extractConfiguratorKeys(string $path): array
{
    $content = file_get_contents($path);
    preg_match_all("/\\\$config\\['([a-z0-9_]+)'\\]|\\\$config\\[\"([a-z0-9_]+)\"\\]/", $content, $matches);

    return array_values(array_unique(array_filter(array_merge($matches[1] ?? [], $matches[2] ?? []))));
}

$configuratorDir = dirname(__DIR__, 3).'/src/Support/FormBuilder/Configurators';

it('registers every configurator key in the default config registry', function () use ($typeToConfigurators, $handlerOnlyKeys, $skipKeys, $configuratorDir): void {
    $gaps = [];

    foreach (FieldType::cases() as $type) {
        $expected = $handlerOnlyKeys[$type->value] ?? [];

        foreach ($typeToConfigurators[$type->value] ?? [] as $configurator) {
            $paths = [
                "{$configuratorDir}/{$configurator}.php",
                "{$configuratorDir}/Concerns/{$configurator}.php",
            ];

            foreach ($paths as $path) {
                if (! is_file($path)) {
                    continue;
                }

                $expected = array_merge($expected, extractConfiguratorKeys($path));
            }
        }

        $expected = array_values(array_unique(array_diff($expected, $skipKeys)));
        $registryKeys = array_keys($type->defaultConfig());
        $missing = array_values(array_diff($expected, $registryKeys));

        if ($missing !== []) {
            $gaps[$type->value] = $missing;
        }
    }

    expect($gaps)->toBe([]);
});

it('generates one admin control per configurable registry key for every field type', function (): void {
    foreach (FieldType::cases() as $type) {
        $keys = FieldTypeAutoAdminSchema::configurableKeysFor($type);

        if ($keys === []) {
            expect(FieldTypeAutoAdminSchema::hasConfigurableSettings($type))->toBeFalse();

            continue;
        }

        expect(count(FieldTypeAutoAdminSchema::schemaComponentsFor($type)))->toBe(count($keys));
    }
});

it('documents native filament types without configurable settings', function (): void {
    $native = [
        FieldType::SingleLineText,
        FieldType::MultiLineText,
        FieldType::RichText,
        FieldType::Markdown,
        FieldType::Password,
        FieldType::Search,
        FieldType::Checkbox,
        FieldType::Hidden,
        FieldType::ReadOnly,
        FieldType::KeyValue,
        FieldType::Code,
        FieldType::Json,
    ];

    foreach ($native as $type) {
        expect(FieldTypeAutoAdminSchema::hasConfigurableSettings($type))->toBeFalse();
    }
});

it('registers admin settings for email url and icon picker types', function (): void {
    expect(FieldTypeAutoAdminSchema::hasConfigurableSettings(FieldType::Email))->toBeTrue()
        ->and(FieldTypeAutoAdminSchema::hasConfigurableSettings(FieldType::Url))->toBeTrue()
        ->and(FieldTypeAutoAdminSchema::hasConfigurableSettings(FieldType::IconPicker))->toBeTrue()
        ->and(FieldTypeAutoAdminSchema::hasConfigurableSettings(FieldType::File))->toBeTrue()
        ->and(FieldTypeAutoAdminSchema::configurableKeysFor(FieldType::File))->toContain('disk', 'allow_webcam_upload');
});

it('delegates optionable and matrix keys to dedicated repeaters', function (): void {
    expect(FieldTypeAutoAdminSchema::configurableKeysFor(FieldType::Select))
        ->not->toContain('options')
        ->and(FieldTypeAutoAdminSchema::configurableKeysFor(FieldType::MatrixChoice))
        ->not->toContain('rows', 'columns');
});

it('covers signature extended settings in registry and admin', function (): void {
    expect(FieldType::Signature->defaultConfig())
        ->toHaveKeys(['trackpad_glide', 'guidelines', 'view_box_width', 'undo_icon'])
        ->and(FieldTypeAutoAdminSchema::configurableKeysFor(FieldType::Signature))
        ->toContain('trackpad_glide', 'guidelines', 'view_box_width', 'undo_icon');
});

it('keeps reserved option keys out of type settings storage', function (): void {
    foreach (FieldType::cases() as $type) {
        $reserved = FlexFieldTypeSettingsStorage::reservedConfigKeys($type);
        $adminKeys = FieldTypeAutoAdminSchema::configurableKeysFor($type);

        foreach ($reserved as $key) {
            expect($adminKeys)->not->toContain($key);
        }
    }
});
