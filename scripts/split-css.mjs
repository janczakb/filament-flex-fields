/**
 * Splits resources/css/plugin.css into modular source files.
 */
import fs from 'node:fs';
import path from 'node:path';

const packageRoot = path.resolve(import.meta.dirname, '..');
const cssRoot = path.join(packageRoot, 'resources/css');
const pluginPath = path.join(cssRoot, 'plugin.css');

const lines = fs.readFileSync(pluginPath, 'utf8').split('\n');

function slice(startLine, endLine) {
    return lines.slice(startLine - 1, endLine).join('\n').trimEnd() + '\n';
}

function write(relativePath, content) {
    if (! content.trim()) {
        return;
    }

    const fullPath = path.join(cssRoot, relativePath);
    fs.mkdirSync(path.dirname(fullPath), { recursive: true });
    fs.writeFileSync(fullPath, content);
}

/** @type {Array<{ file: string, ranges: Array<[number, number]> }>} */
const extractions = [
    { file: 'components/number-stepper.css', ranges: [[18, 310]] },
    { file: 'components/segment-control.css', ranges: [[311, 644]] },
    { file: 'core/switch.css', ranges: [[645, 1167], [1503, 1694]] },
    { file: 'core/item-card.css', ranges: [[1168, 1297], [1488, 1501]] },
    { file: 'components/hold-confirm-action.css', ranges: [[1298, 1487]] },
    { file: 'components/track-slider.css', ranges: [[1695, 1854]] },
    { file: 'components/flex-slider.css', ranges: [[1855, 2715]] },
    { file: 'playground/playground.css', ranges: [[2716, 3041]] },
    { file: 'components/traffic-split.css', ranges: [[3043, 3173]] },
    { file: 'core/choice-cards.css', ranges: [[3175, 3667]] },
    { file: 'core/rating.css', ranges: [[3668, 3832]] },
    { file: 'components/price-range.css', ranges: [[3835, 4089]] },
    { file: 'components/flex-textarea.css', ranges: [[4090, 4616]] },
    { file: 'components/flex-text-input.css', ranges: [[4617, 5109]] },
    { file: 'core/hint-icon.css', ranges: [[5110, 5130]] },
    { file: 'core/hint-tooltips.css', ranges: [[5132, 5199]] },
    { file: 'core/color-swatch.css', ranges: [[5200, 5327]] },
    { file: 'components/country-field.css', ranges: [[5329, 5594]] },
    { file: 'components/timezone-field.css', ranges: [[5595, 5839]] },
    { file: 'components/currency-field.css', ranges: [[5840, 6218]] },
    { file: 'components/user-select-inline.css', ranges: [[6225, 6748]] },
    { file: 'core/tables/user-column.css', ranges: [[6749, 6804]] },
    { file: 'playground/user-column-playground.css', ranges: [[6805, 6839], [6870, 6872]] },
    { file: 'core/tables/rating-column.css', ranges: [[6841, 6868]] },
    { file: 'components/map-picker.css', ranges: [[6874, 7607]] },
    { file: 'components/signature-field.css', ranges: [[7608, 8004]] },
    { file: 'components/address-autocomplete.css', ranges: [[8005, 8055]] },
    { file: 'components/flex-color-picker.css', ranges: [[8056, 8467]] },
    { file: 'components/title-slug-fused.css', ranges: [[8468, 8552]] },
    { file: 'components/slug-field.css', ranges: [[8553, 9066]] },
];

for (const { file, ranges } of extractions) {
    write(file, ranges.map(([start, end]) => slice(start, end)).join('\n'));
}

const videoFieldPath = path.join(cssRoot, 'components/video-field.css');
const videoLines = fs.readFileSync(videoFieldPath, 'utf8').split('\n');

write('components/video-field-only.css', videoLines.slice(0, 765).join('\n').trimEnd() + '\n');
write('components/audio-field.css', videoLines.slice(765, 1044).join('\n').trimEnd() + '\n');
write('components/flex-verification-code.css', videoLines.slice(1044, 1205).join('\n').trimEnd() + '\n');
write('components/flex-checklist.css', videoLines.slice(1205).join('\n').trimEnd() + '\n');

console.log('CSS split complete.');
