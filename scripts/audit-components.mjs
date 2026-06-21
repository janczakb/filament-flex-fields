#!/usr/bin/env node
/**
 * Per-component asset & documentation audit (DEVELOPMENT.md §20).
 * Usage: node scripts/audit-components.mjs [--json]
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');

const PHP_LIMIT = 500;
const JS_LIMIT = 400;
const DIST_CSS = path.join(ROOT, 'resources/dist/css');
const DIST_JS = path.join(ROOT, 'resources/dist/components');
const CSS_ENTRIES = path.join(ROOT, 'resources/css/entries');
const JS_COMPONENTS = path.join(ROOT, 'resources/js/components');
const VIEWS = path.join(ROOT, 'resources/views');
const DOCS = path.join(ROOT, 'docs');

const SKIP_COMPONENTS = new Set(['TranslatableTabs', 'TitleSlugField']);

const CSS_ONLY_STYLESHEETS = new Set([
    'teleported-menu', 'tag-chips', 'user-display', 'emoji-picker', 'switch',
    'item-card', 'track-slider', 'progress-bar', 'progress-circle', 'color-swatch',
    'cover-card', 'choice-cards', 'rich-editor-field', 'user-column', 'rating-column',
    'icon-column', 'map-picker-dropdown', 'matrix-choice-field',
]);

const STYLESHEET_ALPINE_ENTRY_ALIASES = {
    'user-select': 'select-field',
};

const PLAYGROUND_STYLESHEET_ALIASES = {
    'date-time-fields': 'flex-date-time-field',
    'file-upload': 'flex-file-upload',
    'verification-code': 'flex-verification-code',
    'flex-radiolist': 'flex-checklist',
    'matrix-choice': 'matrix-choice-field',
    'rating': 'rating-field',
    'flex-rich-editor': 'rich-editor-field',
};

const HEAVY_INTERSECT_FIELDS = new Set([
    'flex-date-time-field', 'flex-file-upload', 'select-field', 'user-select',
    'barcode-scanner-field', 'icon-picker-field',
]);

/** Explicit stylesheet / view / alpine / JS mappings */
const STYLESHEET_BY_CLASS = {
    FlexRichEditor: 'rich-editor-field',
    FlexTextInput: 'flex-text-input',
    FlexTextareaField: 'flex-textarea',
    PriceRangeField: 'price-range',
    DualListboxField: 'dual-listbox',
    MapPickerField: 'map-picker',
    AddressAutocompleteField: 'address-autocomplete',
    CreditCardField: 'credit-card',
    SwitchField: 'switch',
    CellSwitch: 'switch',
    ChoiceCards: 'choice-cards',
    ChoiceCheckboxCards: 'choice-cards',
    FlexRadiolist: 'flex-checklist',
    FlexImageUpload: 'flex-file-upload',
    FlexSpatieMediaLibraryFileUpload: 'flex-file-upload',
    FlexSpatieTagsField: 'tags-field',
    CellSlider: 'track-slider',
    FlexDateField: 'flex-date-time-field',
    FlexDatePicker: 'flex-date-time-field',
    FlexTimeField: 'flex-date-time-field',
    FlexDateTimePicker: 'flex-date-time-field',
    FlexDateRangeField: 'flex-date-time-field',
    FlexDurationField: 'flex-date-time-field',
    FlexTimeRangeField: 'flex-date-time-field',
    FlexMonthPicker: 'flex-date-time-field',
    FlexYearPicker: 'flex-date-time-field',
    FlexDateTimeField: 'flex-date-time-field',
    FlexColorPickerField: 'flex-color-picker',
    FlexVerificationCode: 'flex-verification-code',
    FlexTimeSegmentsField: 'flex-time-segments',
    FlexChecklist: 'flex-checklist',
    MatrixChoiceField: 'matrix-choice-field',
    ColorSwatchField: 'color-swatch',
    NumberStepper: 'number-stepper',
    SegmentControl: 'segment-control',
    TranslatableFields: 'segment-tabs',
    SegmentTabs: 'segment-tabs',
    TrackSlider: 'track-slider',
    TrafficSplit: 'traffic-split',
    RatingField: 'rating-field',
    VoiceNoteRecorderField: 'voice-note-recorder-field',
    LinkPreviewField: 'link-preview-field',
    BarcodeScannerField: 'barcode-scanner-field',
    SocialLinksField: 'social-links-field',
    IconPickerField: 'icon-picker-field',
    FlexFileUpload: 'flex-file-upload',
    HoldConfirmAction: 'hold-confirm-action',
    IconColumn: 'icon-column',
    UserColumn: 'user-column',
    RatingColumn: 'rating-column',
    ItemCard: 'item-card',
    ItemCardGroup: 'item-card',
    ItemCardStack: 'item-card',
    AudioField: 'audio-field',
    CountryField: 'country-field',
    CurrencyField: 'currency-field',
    PhoneField: 'phone-field',
    ScheduleField: 'schedule-field',
    SelectField: 'select-field',
    SignatureField: 'signature-field',
    SlugField: 'slug-field',
    TagsField: 'tags-field',
    TimezoneField: 'timezone-field',
    VideoField: 'video-field',
};

const LOAD_STYLESHEET_BY_CLASS = {
    FlexRadiolist: ['flex-radiolist'],
    TranslatableFields: ['segment-tabs'],
    SegmentTabs: ['segment-tabs'],
    UserSelect: ['select-field', 'user-select'],
    HoldConfirmAction: ['hold-confirm-action'],
};

const ALPINE_BY_CLASS = {
    FlexRichEditor: 'flex-rich-editor',
    TranslatableFields: 'segment-tabs',
    SegmentTabs: 'segment-tabs',
    HoldConfirmAction: 'hold-confirm-action',
};

const JS_BY_CLASS = {
    FlexRichEditor: 'flex-rich-editor',
    TranslatableFields: 'segment-tabs',
    SegmentTabs: 'segment-tabs',
    HoldConfirmAction: 'hold-confirm-action',
};

const INHERIT_VIEW_FROM = {
    FlexImageUpload: 'FlexFileUpload',
    FlexSpatieMediaLibraryFileUpload: 'FlexFileUpload',
    FlexSpatieTagsField: 'TagsField',
    CellSlider: 'TrackSlider',
    FlexDateField: 'FlexDateTimeField',
    FlexDatePicker: 'FlexDateTimeField',
    FlexTimeField: 'FlexDateTimeField',
    FlexDateTimePicker: 'FlexDateTimeField',
    FlexDateRangeField: 'FlexDateTimeField',
    FlexDurationField: 'FlexDateTimeField',
    FlexTimeRangeField: 'FlexDateTimeField',
    FlexMonthPicker: 'FlexDateTimeField',
    FlexYearPicker: 'FlexDateTimeField',
    CellSwitch: 'SwitchField',
    TranslatableFields: 'SegmentTabs',
};

const STYLESHEET_VIA_PHP = new Set(['IconColumn', 'UserColumn', 'RatingColumn']);

const TABLE_COLUMN_BLADES = {
    IconColumn: ['tables/columns/icon-column.blade.php'],
    RatingColumn: ['tables/columns/rating-column.blade.php'],
    UserColumn: ['tables/columns/user-column-rich.blade.php', 'tables/columns/user-column-stack.blade.php'],
};

const HOLD_CONFIRM = {
    blade: 'actions/hold-confirm.blade.php',
    stylesheet: 'hold-confirm-action',
};

function read(file) {
    return fs.existsSync(file) ? fs.readFileSync(file, 'utf8') : '';
}

function lineCount(file) {
    return fs.existsSync(file) ? read(file).split('\n').length : 0;
}

function parsePhpStringList(content, constName) {
    const re = new RegExp(`const\\s+${constName}\\s*=\\s*\\[([\\s\\S]*?)\\];`, 'm');
    const m = content.match(re);
    if (!m) return [];
    return [...m[1].matchAll(/'([^']+)'/g)].map((x) => x[1]);
}

function parsePhpAssocList(content, constName) {
    const re = new RegExp(`const\\s+${constName}\\s*=\\s*\\[([\\s\\S]*?)\\];`, 'm');
    const m = content.match(re);
    if (!m) return {};
    const out = {};
    const entryRe = /'([^']+)'\s*=>\s*\[([^\]]*)\]/g;
    let em;
    while ((em = entryRe.exec(m[1])) !== null) {
        out[em[1]] = [...em[2].matchAll(/'([^']+)'/g)].map((x) => x[1]);
    }
    return out;
}

function resolveStylesheetId(id) {
    return PLAYGROUND_STYLESHEET_ALIASES[id] ?? id;
}

function stylesheetsFor(component, lazyList, deps) {
    const resolved = resolveStylesheetId(component);
    const visited = new Set();
    const out = [];

    function walk(comp) {
        if (visited.has(comp)) return;
        visited.add(comp);
        for (const d of deps[comp] ?? []) walk(d);
        if (lazyList.includes(comp) && !out.includes(comp)) out.push(comp);
    }

    walk(resolved);
    return out;
}

function shouldLoadStylesheetsFor(component, lazyList, deps) {
    const resolved = resolveStylesheetId(component);
    return lazyList.includes(resolved) || Object.prototype.hasOwnProperty.call(deps, resolved);
}

function viewNameToBladeRel(viewName) {
    return viewName.replace(/\./g, '/') + '.blade.php';
}

function discoverPhpComponents() {
    const dirs = [
        'src/Filament/Forms/Components',
        'src/Filament/Forms/Components/Spatie',
        'src/Filament/Schemas/Components',
        'src/Filament/Tables/Columns',
    ];
    const names = new Set(['HoldConfirmAction']);
    for (const base of dirs) {
        const full = path.join(ROOT, base);
        if (!fs.existsSync(full)) continue;
        for (const ent of fs.readdirSync(full, { withFileTypes: true })) {
            if (ent.isFile() && ent.name.endsWith('.php')) {
                names.add(path.basename(ent.name, '.php'));
            }
        }
    }
    for (const skip of SKIP_COMPONENTS) names.delete(skip);
    return [...names].sort();
}

function findPhpPath(name) {
    if (name === 'HoldConfirmAction') return null;
    const candidates = [
        `src/Filament/Forms/Components/${name}.php`,
        `src/Filament/Forms/Components/Spatie/${name}.php`,
        `src/Filament/Schemas/Components/${name}.php`,
        `src/Filament/Tables/Columns/${name}.php`,
    ];
    for (const c of candidates) {
        const p = path.join(ROOT, c);
        if (fs.existsSync(p)) return p;
    }
    return null;
}

function extractViewFromPhp(phpPath) {
    const m = read(phpPath).match(/protected\s+string\s+\$view\s*=\s*'filament-flex-fields::([^']+)'/);
    return m ? viewNameToBladeRel(m[1]) : null;
}

function buildViewMap(names) {
    const map = {};
    for (const name of names) {
        const p = findPhpPath(name);
        if (p) {
            const v = extractViewFromPhp(p);
            if (v) map[name] = v;
        }
    }
    return map;
}

function resolveBladePath(name, viewMap) {
    if (name === 'HoldConfirmAction') {
        return path.join(VIEWS, HOLD_CONFIRM.blade);
    }
    if (TABLE_COLUMN_BLADES[name]) {
        const first = TABLE_COLUMN_BLADES[name].find((rel) => fs.existsSync(path.join(VIEWS, rel)));
        return first ? path.join(VIEWS, first) : null;
    }
    const parent = INHERIT_VIEW_FROM[name];
    if (parent && viewMap[parent]) {
        return path.join(VIEWS, viewMap[parent]);
    }
    if (name === 'CellSwitch') {
        return path.join(VIEWS, 'forms/components/cell-switch.blade.php');
    }
    const rel = viewMap[name];
    return rel ? path.join(VIEWS, rel) : null;
}

function bladeLoadIds(name, stylesheet) {
    if (LOAD_STYLESHEET_BY_CLASS[name]) return LOAD_STYLESHEET_BY_CLASS[name];
    if (STYLESHEET_VIA_PHP.has(name)) return [];
    return [stylesheet];
}

function collectBladeContent(name, bladePath) {
    if (name === 'UserSelect') {
        return read(path.join(VIEWS, 'forms/components/select-field.blade.php'));
    }
    return read(bladePath);
}

function parseComponentsMdListed() {
    const content = read(path.join(ROOT, 'COMPONENTS.md'));
    const skip = new Set([
        'Overview', 'Documentation conventions', 'Control size',
        'Inherited Filament field API', 'Assets & playground',
        'Rich card option shape', 'Rich select option shape',
        'Dual listbox option shape', 'Layout components — quick comparison',
        'Form layout patterns', 'Date & time fields',
    ]);
    const listed = new Set();
    for (const m of content.matchAll(/^## ([^\n]+)/gm)) {
        const h = m[1].trim();
        if (!skip.has(h) && !h.includes('Part ')) listed.add(h.replace(/ &.*/, '').trim());
    }
    return listed;
}

const DOC_FILE_BY_CLASS = {
    FlexTextInput: 'flextextinput.md',
    FlexTextareaField: 'flextextareafield.md',
    FlexVerificationCode: 'flexverificationcode.md',
    UserSelect: 'userselect.md',
    UserColumn: 'usercolumn.md',
    RatingColumn: 'ratingcolumn.md',
    IconColumn: 'iconcolumn.md',
    PhoneField: 'phonefield.md',
    CountryField: 'countryfield.md',
    TimezoneField: 'timezonefield.md',
    LinkPreviewField: 'link-preview-field.md',
    BarcodeScannerField: 'barcode-scanner-field.md',
    SocialLinksField: 'social-links-field.md',
    SlugField: 'slugfield-and-titleslugfield.md',
    AddressAutocompleteField: 'addressautocompletefield.md',
    NumberStepper: 'numberstepper.md',
    CurrencyField: 'currencyfield.md',
    FlexSlider: 'flexslider.md',
    TrackSlider: 'trackslider.md',
    CellSlider: 'trackslider.md',
    PriceRangeField: 'pricerangefield.md',
    TrafficSplit: 'trafficsplit.md',
    SwitchField: 'switchfield.md',
    CellSwitch: 'switchfield.md',
    SegmentControl: 'segmentcontrol.md',
    ChoiceCards: 'choicecards.md',
    ChoiceCheckboxCards: 'choicecheckboxcards.md',
    FlexChecklist: 'flexchecklist.md',
    FlexRadiolist: 'flexradiolist.md',
    MatrixChoiceField: 'matrixchoicefield.md',
    SelectField: 'selectfield.md',
    DualListboxField: 'duallistboxfield.md',
    TagsField: 'tags-field.md',
    FlexSpatieTagsField: 'tags-field.md',
    FlexDateField: 'date-and-time-fields.md',
    FlexDatePicker: 'date-and-time-fields.md',
    FlexTimeField: 'date-and-time-fields.md',
    FlexTimeSegmentsField: 'date-and-time-fields.md',
    FlexDateTimePicker: 'date-and-time-fields.md',
    FlexDateRangeField: 'date-and-time-fields.md',
    FlexDurationField: 'date-and-time-fields.md',
    FlexTimeRangeField: 'date-and-time-fields.md',
    FlexMonthPicker: 'date-and-time-fields.md',
    FlexYearPicker: 'date-and-time-fields.md',
    FlexDateTimeField: 'date-and-time-fields.md',
    ScheduleField: 'schedule-field.md',
    ColorSwatchField: 'colorswatchfield.md',
    FlexColorPickerField: 'flexcolorpickerfield.md',
    FlexFileUpload: 'flexfileupload-and-fleximageupload.md',
    FlexImageUpload: 'flexfileupload-and-fleximageupload.md',
    FlexSpatieMediaLibraryFileUpload: 'flexfileupload-and-fleximageupload.md',
    VideoField: 'videofield.md',
    AudioField: 'audiofield.md',
    VoiceNoteRecorderField: 'voicenoterecorderfield.md',
    MapPickerField: 'mappickerfield.md',
    SignatureField: 'signaturefield.md',
    CreditCardField: 'creditcardfield.md',
    RatingField: 'ratingfield.md',
    SegmentTabs: 'segmenttabs.md',
    TranslatableFields: 'translatablefields.md',
    ItemCard: 'itemcard.md',
    ItemCardGroup: 'itemcardgroup.md',
    ItemCardStack: 'itemcardstack.md',
    CoverCard: 'covercard.md',
    ProgressBar: 'progressbar.md',
    ProgressCircle: 'progresscircle.md',
    FlexRichEditor: 'flex-rich-editor.md',
    IconPickerField: 'icon-picker-field.md',
    HoldConfirmAction: 'hold-confirm-action.md',
};

function docExpected(name, componentsMdListed) {
    if (DOC_FILE_BY_CLASS[name]) return true;
    return componentsMdListed.has(name);
}

function auditComponent(name, ctx) {
    const { lazyList, deps, manifest, componentsMdListed, viewMap } = ctx;
    const issues = [];
    const warnings = [];
    const checks = {};

    const phpPath = findPhpPath(name);
    const phpLines = phpPath ? lineCount(phpPath) : 0;

    checks.php = name === 'HoldConfirmAction' || (phpPath && fs.existsSync(phpPath)) ? 'ok' : 'fail';
    if (checks.php === 'fail') issues.push('brak pliku PHP');
    checks.phpLines = phpLines > PHP_LIMIT ? 'warn' : 'ok';
    if (checks.phpLines === 'warn') warnings.push(`PHP ${phpLines} linii (limit ${PHP_LIMIT})`);

    const stylesheet = STYLESHEET_BY_CLASS[name] ?? name.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase();
    const loadIds = bladeLoadIds(name, stylesheet);
    const primaryLoadId = loadIds[0] ?? stylesheet;

    const bladePath = resolveBladePath(name, viewMap);
    const bladeExists = bladePath && fs.existsSync(bladePath);
    checks.blade = bladeExists ? 'ok' : 'fail';
    if (!bladeExists) issues.push('brak blade view');

    const bladeContent = bladeExists ? collectBladeContent(name, bladePath) : '';

    if (STYLESHEET_VIA_PHP.has(name)) {
        const phpContent = phpPath ? read(phpPath) : '';
        checks.bladeStylesheet = phpContent.includes("enqueueFor('") ? 'ok' : 'fail';
        if (checks.bladeStylesheet === 'fail') issues.push('brak FlexFieldStylesheetQueue::enqueueFor w PHP');
    } else if (loadIds.length) {
        const missing = loadIds.filter((id) => !bladeContent.includes(`'component' => '${id}'`));
        checks.bladeStylesheet = missing.length === 0 ? 'ok' : 'fail';
        if (missing.length) issues.push(`blade brak load-stylesheet: ${missing.join(', ')}`);
    } else {
        checks.bladeStylesheet = 'n/a';
    }

    const registryOk = shouldLoadStylesheetsFor(primaryLoadId, lazyList, deps);
    checks.lazyRegistry = registryOk ? 'ok' : 'fail';
    if (!registryOk) issues.push(`stylesheet ${primaryLoadId} poza LAZY/DEPS`);

    const bundles = stylesheetsFor(primaryLoadId, lazyList, deps);
    const cssEntryFails = [];
    const cssDistFails = [];
    const cssBaselineFails = [];
    for (const ss of bundles) {
        const entry = path.join(CSS_ENTRIES, `${ss}.css`);
        const dist = path.join(DIST_CSS, `${ss}.css`);
        if (!fs.existsSync(entry)) cssEntryFails.push(ss);
        else {
            const ec = read(entry);
            if (!ec.includes('utilities-baseline.css')) cssBaselineFails.push(ss);
            if (/@import\s+["']tailwindcss["']/.test(ec)) cssBaselineFails.push(`${ss}:raw tailwind`);
        }
        if (!fs.existsSync(dist)) cssDistFails.push(ss);
    }
    checks.cssEntry = cssEntryFails.length === 0 ? 'ok' : 'fail';
    checks.cssDist = cssDistFails.length === 0 ? 'ok' : 'fail';
    checks.cssBaseline = cssBaselineFails.length === 0 ? 'ok' : 'fail';
    if (cssEntryFails.length) issues.push(`brak CSS entry: ${cssEntryFails.join(', ')}`);
    if (cssDistFails.length) issues.push(`brak dist CSS: ${cssDistFails.join(', ')}`);
    if (cssBaselineFails.length) issues.push(`CSS baseline: ${cssBaselineFails.join(', ')}`);

    const resolvedSs = resolveStylesheetId(stylesheet);
    const alpineEntry = ALPINE_BY_CLASS[name] ?? STYLESHEET_ALPINE_ENTRY_ALIASES[resolvedSs] ?? resolvedSs;
    const cssOnly = CSS_ONLY_STYLESHEETS.has(resolvedSs);
    const manifestOk = cssOnly || Object.prototype.hasOwnProperty.call(manifest, alpineEntry);
    checks.alpine = manifestOk ? 'ok' : 'fail';
    if (!manifestOk) issues.push(`brak alpine manifest [${alpineEntry}]`);

    const jsName = JS_BY_CLASS[name] ?? alpineEntry;
    const jsFile = path.join(JS_COMPONENTS, `${jsName}.js`);
    const jsDist = path.join(DIST_JS, `${alpineEntry}.js`);
    const jsLines = lineCount(jsFile);

    if (cssOnly) {
        checks.js = 'ok';
        checks.jsLines = 'ok';
        checks.jsDist = 'ok';
    } else {
        checks.jsDist = fs.existsSync(jsDist) ? 'ok' : 'fail';
        if (checks.jsDist === 'fail') issues.push(`brak dist JS ${alpineEntry}.js`);
        checks.js = fs.existsSync(jsFile) ? 'ok' : (checks.jsDist === 'ok' ? 'warn' : 'fail');
        if (!fs.existsSync(jsFile) && checks.js === 'fail') issues.push(`brak JS source ${jsName}.js`);
        checks.jsLines = jsLines > JS_LIMIT ? 'warn' : 'ok';
        if (checks.jsLines === 'warn') warnings.push(`JS entry ${jsLines} linii (limit ${JS_LIMIT})`);
    }

    const heavyKey = resolveStylesheetId(stylesheet);
    if (HEAVY_INTERSECT_FIELDS.has(heavyKey)) {
        const hasIntersect = bladeContent.includes('lazy-alpine-mount') || bladeContent.includes('x-intersect');
        checks.intersect = hasIntersect ? 'ok' : 'fail';
        if (!hasIntersect) issues.push('brak lazy-alpine-mount / x-intersect');
    } else {
        checks.intersect = 'n/a';
    }

    const docPath = DOC_FILE_BY_CLASS[name] ? path.join(DOCS, DOC_FILE_BY_CLASS[name]) : null;
    const docExists = docPath && fs.existsSync(docPath);
    if (docExpected(name, componentsMdListed)) {
        checks.doc = docExists ? 'ok' : 'fail';
        if (!docExists) issues.push(`brak docs/${DOC_FILE_BY_CLASS[name] ?? '?'}`);
    } else {
        checks.doc = 'n/a';
    }

    const status = issues.length ? 'FAIL' : warnings.length ? 'WARN' : 'OK';

    return {
        name,
        phpLines: name === 'HoldConfirmAction' ? '—' : phpLines,
        jsLines: cssOnly ? '—' : (fs.existsSync(jsFile) ? jsLines : '—'),
        checks,
        issues,
        warnings,
        status,
        stylesheet,
        bundles: bundles.join('+') || '—',
    };
}

function collectBladeStylesheetRefs() {
    const refs = new Set();
    function walk(dir) {
        for (const ent of fs.readdirSync(dir, { withFileTypes: true })) {
            const p = path.join(dir, ent.name);
            if (ent.isDirectory()) walk(p);
            else if (ent.name.endsWith('.blade.php')) {
                const c = read(p);
                if (!c.includes('load-stylesheet')) continue;
                for (const m of c.matchAll(/'component'\s*=>\s*'([^']+)'/g)) refs.add(m[1]);
            }
        }
    }
    walk(VIEWS);
    return refs;
}

function main() {
    const assetsPhp = read(path.join(ROOT, 'src/Support/FlexFieldAssets.php'));
    const lazyList = parsePhpStringList(assetsPhp, 'LAZY_COMPONENT_STYLESHEETS');
    const deps = parsePhpAssocList(assetsPhp, 'STYLESHEET_DEPENDENCIES');
    const manifest = JSON.parse(read(path.join(DIST_JS, 'alpine-manifest.json')) || '{}');

    const names = discoverPhpComponents();
    const viewMap = buildViewMap(names);
    const componentsMdListed = parseComponentsMdListed();
    const ctx = { lazyList, deps, manifest, componentsMdListed, viewMap };
    const rows = names.map((n) => auditComponent(n, ctx));

    const lazyCssEntryFails = lazyList.filter((ss) => !fs.existsSync(path.join(CSS_ENTRIES, `${ss}.css`)));
    const lazyCssDistFails = lazyList.filter((ss) => !fs.existsSync(path.join(DIST_CSS, `${ss}.css`)));
    const alpineEntries = Object.keys(manifest).filter((k) => !k.startsWith('__'));
    const alpineDistFails = alpineEntries.filter((e) => !fs.existsSync(path.join(DIST_JS, `${e}.js`)));

    const bladeRefs = collectBladeStylesheetRefs();
    const allReferenced = new Set([...bladeRefs, ...bladeRefs].map(resolveStylesheetId));
    for (const id of bladeRefs) {
        for (const ss of stylesheetsFor(id, lazyList, deps)) allReferenced.add(ss);
    }
    const orphanEntries = fs.readdirSync(CSS_ENTRIES)
        .filter((f) => f.endsWith('.css'))
        .map((f) => path.basename(f, '.css'))
        .filter((id) => !lazyList.includes(id) && !allReferenced.has(id));

    if (process.argv.includes('--json')) {
        console.log(JSON.stringify({ rows, lazyCssEntryFails, lazyCssDistFails, alpineDistFails, orphanEntries, total: rows.length }, null, 2));
        return;
    }

    console.log('# Filament Flex Fields — audyt komponentów\n');
    console.log(`Data: ${new Date().toISOString().slice(0, 10)} | Komponentów: ${rows.length} | LAZY stylesheets: ${lazyList.length}\n`);

    console.log('## Tabela podsumowania\n');
    console.log('| Komponent | PHP | Blade | CSS | Alpine | JS linii | Status |');
    console.log('|-----------|-----|-------|-----|--------|----------|--------|');
    for (const r of rows) {
        const c = r.checks;
        const php = c.php === 'fail' ? '❌' : c.phpLines === 'warn' ? `⚠️${r.phpLines}` : `✅${r.phpLines}`;
        const blade = c.blade === 'fail' || c.bladeStylesheet === 'fail' ? '❌' : '✅';
        const css = c.cssEntry === 'fail' || c.cssDist === 'fail' || c.cssBaseline === 'fail' ? '❌' : '✅';
        const alpine = c.alpine === 'fail' ? '❌' : '✅';
        const js = c.jsLines === 'warn' ? `⚠️${r.jsLines}` : c.js === 'fail' ? '❌' : `✅${r.jsLines}`;
        console.log(`| ${r.name} | ${php} | ${blade} | ${css} | ${alpine} | ${js} | ${r.status} |`);
    }

    const critical = rows.filter((r) => r.status === 'FAIL');
    const warned = rows.filter((r) => r.warnings.length > 0);
    const allGreen = rows.filter((r) => r.status === 'OK');

    console.log('\n## Krytyczne (must fix)\n');
    let anyGlobal = false;
    for (const r of critical) {
        console.log(`- **${r.name}**: ${r.issues.join('; ')}`);
    }
    if (lazyCssEntryFails.length) {
        anyGlobal = true;
        console.log(`- **LAZY registry**: brak CSS entry: ${lazyCssEntryFails.join(', ')}`);
    }
    if (lazyCssDistFails.length) {
        anyGlobal = true;
        console.log(`- **LAZY registry**: brak dist CSS: ${lazyCssDistFails.join(', ')}`);
    }
    if (alpineDistFails.length) {
        anyGlobal = true;
        console.log(`- **Alpine manifest**: brak dist JS: ${alpineDistFails.join(', ')}`);
    }
    if (orphanEntries.length) {
        anyGlobal = true;
        console.log(`- **Orphan CSS entries** (nie w LAZY ani blade): ${orphanEntries.join(', ')}`);
    }
    if (critical.length === 0 && !anyGlobal) {
        console.log('_Brak krytycznych luk._');
    }

    console.log('\n## Ostrzeżenia (over budget / docs)\n');
    for (const r of warned) {
        console.log(`- **${r.name}**: ${r.warnings.join('; ')}`);
    }
    if (!warned.length) console.log('_Brak ostrzeżeń budżetowych._');

    console.log('\n## Statystyki\n');
    console.log(`- ✅ All-green: **${allGreen.length}** / ${rows.length}`);
    console.log(`- ⚠️ Z ostrzeżeniami: **${warned.length}**`);
    console.log(`- ❌ Krytyczne: **${critical.length}**`);

    if (critical.length > 0 || anyGlobal) {
        process.exitCode = 1;
    }
}

main();
