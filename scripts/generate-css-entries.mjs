import fs from 'node:fs';
import path from 'node:path';

const entriesRoot = path.resolve(import.meta.dirname, '../resources/css/entries');

/** @type {Array<{ name: string, imports: string[], sources: string[] }>} */
const components = [
    {
        name: 'number-stepper',
        imports: ['../components/number-stepper.css'],
        sources: ['../../src/Filament/Forms/Components/NumberStepper.php', '../views/forms/components/number-stepper.blade.php'],
    },
    {
        name: 'segment-control',
        imports: ['../components/segment-control.css'],
        sources: [
            '../../src/Filament/Forms/Components/SegmentControl.php',
            '../views/forms/components/segment-control.blade.php',
            '../views/schemas/components/segment-tabs.blade.php',
            '../views/schemas/components/segment-tabs/segment-tab.blade.php',
        ],
    },
    {
        name: 'traffic-split',
        imports: ['../components/traffic-split.css'],
        sources: ['../../src/Filament/Forms/Components/TrafficSplit.php', '../views/forms/components/traffic-split.blade.php'],
    },
    {
        name: 'dual-listbox',
        imports: ['../components/dual-listbox.css'],
        sources: ['../../src/Filament/Forms/Components/DualListboxField.php', '../views/forms/components/dual-listbox-field.blade.php'],
    },
    {
        name: 'price-range',
        imports: ['../components/price-range.css'],
        sources: ['../../src/Filament/Forms/Components/PriceRangeField.php', '../views/forms/components/price-range-field.blade.php'],
    },
    {
        name: 'flex-textarea',
        imports: ['../components/flex-textarea.css'],
        sources: ['../../src/Filament/Forms/Components/FlexTextareaField.php', '../views/forms/components/flex-textarea-field.blade.php'],
    },
    {
        name: 'flex-text-input',
        imports: ['../components/flex-text-input.css'],
        sources: ['../../src/Filament/Forms/Components/FlexTextInput.php', '../views/forms/components/flex-text-input-field.blade.php'],
    },
    {
        name: 'credit-card',
        imports: ['../components/credit-card.css'],
        sources: ['../../src/Filament/Forms/Components/CreditCardField.php', '../views/forms/components/credit-card-field.blade.php'],
    },
    {
        name: 'phone-field',
        imports: ['../components/phone-field.css'],
        sources: ['../../src/Filament/Forms/Components/PhoneField.php', '../views/forms/components/phone-field.blade.php'],
    },
    {
        name: 'country-field',
        imports: ['../components/country-field.css'],
        sources: ['../../src/Filament/Forms/Components/CountryField.php', '../views/forms/components/country-field.blade.php'],
    },
    {
        name: 'timezone-field',
        imports: ['../components/timezone-field.css'],
        sources: ['../../src/Filament/Forms/Components/TimezoneField.php', '../views/forms/components/timezone-field.blade.php'],
    },
    {
        name: 'flex-date-time-field',
        imports: ['../components/flex-date-time.css'],
        sources: ['../../src/Filament/Forms/Components/FlexDateTimeField.php', '../views/forms/components/flex-date-time-field.blade.php'],
    },
    {
        name: 'flex-file-upload',
        imports: ['../components/flex-file-upload.css'],
        sources: ['../../src/Filament/Forms/Components/FlexFileUpload.php', '../views/forms/components/flex-file-upload.blade.php'],
    },
    {
        name: 'currency-field',
        imports: ['../components/currency-field.css'],
        sources: ['../../src/Filament/Forms/Components/CurrencyField.php', '../views/forms/components/currency-field.blade.php'],
    },
    {
        name: 'slug-field',
        imports: ['../components/title-slug-fused.css', '../components/slug-field.css'],
        sources: [
            '../../src/Filament/Forms/Components/SlugField.php',
            '../../src/Filament/Forms/Components/TitleSlugField.php',
            '../views/forms/components/slug-field.blade.php',
        ],
    },
    {
        name: 'video-field',
        imports: ['../components/video-field.css'],
        sources: [
            '../../src/Filament/Forms/Components/VideoField.php',
            '../views/forms/components/video-field.blade.php',
            '../views/forms/components/partials/video-field-controls.blade.php',
        ],
    },
    {
        name: 'audio-field',
        imports: ['../components/audio-field.css'],
        sources: ['../../src/Filament/Forms/Components/AudioField.php', '../views/forms/components/audio-field.blade.php'],
    },
    {
        name: 'flex-slider',
        imports: ['../components/flex-slider.css'],
        sources: ['../../src/Filament/Forms/Components/FlexSlider.php', '../views/forms/components/flex-slider.blade.php'],
    },
    {
        name: 'flex-verification-code',
        imports: ['../components/flex-verification-code.css'],
        sources: ['../../src/Filament/Forms/Components/FlexVerificationCode.php', '../views/forms/components/flex-verification-code.blade.php'],
    },
    {
        name: 'map-picker-dropdown',
        imports: ['../components/map-picker-dropdown.css'],
        sources: [
            '../views/forms/components/map-picker-field.blade.php',
            '../views/forms/components/address-autocomplete-field.blade.php',
        ],
    },
    {
        name: 'map-picker',
        imports: ['../components/map-picker.css'],
        sources: ['../../src/Filament/Forms/Components/MapPickerField.php', '../views/forms/components/map-picker-field.blade.php'],
    },
    {
        name: 'address-autocomplete',
        imports: ['../components/address-autocomplete.css'],
        sources: ['../../src/Filament/Forms/Components/AddressAutocompleteField.php', '../views/forms/components/address-autocomplete-field.blade.php'],
    },
    {
        name: 'signature-field',
        imports: ['../components/signature-field.css'],
        sources: ['../../src/Filament/Forms/Components/SignatureField.php', '../views/forms/components/signature-field.blade.php'],
    },
    {
        name: 'flex-color-picker',
        imports: ['../components/flex-color-picker.css'],
        sources: ['../../src/Filament/Forms/Components/FlexColorPickerField.php', '../views/forms/components/flex-color-picker-field.blade.php'],
    },
    {
        name: 'flex-checklist',
        imports: ['../components/flex-checklist.css'],
        sources: ['../../src/Filament/Forms/Components/FlexChecklist.php', '../views/forms/components/flex-checklist.blade.php'],
    },
    {
        name: 'tags-field',
        imports: ['../components/tags-field.css'],
        sources: ['../../src/Filament/Forms/Components/TagsField.php', '../views/forms/components/tags-field.blade.php'],
    },
];

fs.mkdirSync(entriesRoot, { recursive: true });

for (const { name, imports, sources } of components) {
    const content = [
        '@import "tailwindcss/theme";',
        '@import "tailwindcss/utilities";',
        '@import "../base.css";',
        '',
        ...sources.map((source) => `@source "${source}";`),
        '',
        ...imports.map((file) => `@import "${file}";`),
        '',
    ].join('\n');

    fs.writeFileSync(path.join(entriesRoot, `${name}.css`), content);
}

console.log(`Generated ${components.length} component CSS entries.`);
