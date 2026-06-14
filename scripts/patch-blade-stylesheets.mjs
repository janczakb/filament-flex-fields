import fs from 'node:fs';
import path from 'node:path';

const packageRoot = path.resolve(import.meta.dirname, '..');
const includeLine = (component) => `@include('filament-flex-fields::partials.load-stylesheet', ['component' => '${component}'])`;

/** @type {Array<[string, string]>} */
const patches = [
    ['resources/views/forms/components/number-stepper.blade.php', 'number-stepper'],
    ['resources/views/forms/components/segment-control.blade.php', 'segment-control'],
    ['resources/views/forms/components/traffic-split.blade.php', 'traffic-split'],
    ['resources/views/forms/components/dual-listbox-field.blade.php', 'dual-listbox'],
    ['resources/views/forms/components/price-range-field.blade.php', 'price-range'],
    ['resources/views/forms/components/flex-textarea-field.blade.php', 'flex-textarea'],
    ['resources/views/forms/components/flex-text-input-field.blade.php', 'flex-text-input'],
    ['resources/views/forms/components/credit-card-field.blade.php', 'credit-card'],
    ['resources/views/forms/components/phone-field.blade.php', 'phone-field'],
    ['resources/views/forms/components/country-field.blade.php', 'country-field'],
    ['resources/views/forms/components/timezone-field.blade.php', 'timezone-field'],
    ['resources/views/forms/components/flex-date-time-field.blade.php', 'flex-date-time-field'],
    ['resources/views/forms/components/flex-file-upload.blade.php', 'flex-file-upload'],
    ['resources/views/forms/components/currency-field.blade.php', 'currency-field'],
    ['resources/views/forms/components/slug-field.blade.php', 'slug-field'],
    ['resources/views/forms/components/video-field.blade.php', 'video-field'],
    ['resources/views/forms/components/audio-field.blade.php', 'audio-field'],
    ['resources/views/forms/components/flex-slider.blade.php', 'flex-slider'],
    ['resources/views/forms/components/flex-verification-code.blade.php', 'flex-verification-code'],
    ['resources/views/forms/components/map-picker-field.blade.php', 'map-picker'],
    ['resources/views/forms/components/address-autocomplete-field.blade.php', 'address-autocomplete'],
    ['resources/views/forms/components/signature-field.blade.php', 'signature-field'],
    ['resources/views/forms/components/flex-color-picker-field.blade.php', 'flex-color-picker'],
    ['resources/views/forms/components/flex-checklist.blade.php', 'flex-checklist'],
    ['resources/views/forms/components/flex-radiolist.blade.php', 'flex-radiolist'],
];

for (const [relativePath, component] of patches) {
    const fullPath = path.join(packageRoot, relativePath);
    let content = fs.readFileSync(fullPath, 'utf8');
    const needle = includeLine(component);

    if (content.includes(needle)) {
        continue;
    }

    content = content.replace(
        /(<x-dynamic-component[\s\S]*?>\s*\n)(\s*<div)/,
        `$1    ${needle}\n$2`,
    );

    fs.writeFileSync(fullPath, content);
}

console.log(`Patched ${patches.length} blade templates.`);
