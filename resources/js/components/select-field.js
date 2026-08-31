/**
 * SelectField Alpine entry — first-party headless combobox only (v3).
 *
 * Legacy Filament selectFormComponent patches live in
 * `select-field/select-field-legacy.js` for historical unit/e2e fixtures only
 * and are NOT registered on alpine:init from this production entry.
 */
export {
    findTriggerLabelInOptions,
    populateRepositoryWithTriggerLabels,
    resolveTriggerLabel,
} from './select-field/select-field-trigger-labels.js';

import headlessComboboxAlpine from './select-field/headless-combobox-alpine.js';

export function markSelectFieldShellAttached(shell, attached) {
    if (! shell) {
        return;
    }

    shell.dataset.fffSelectAttached = attached ? 'true' : 'false';
}

export function fffHeadlessSelectField(config = {}) {
    return headlessComboboxAlpine(config);
}

export default fffHeadlessSelectField;

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine === 'undefined') {
            return;
        }

        Alpine.data('fffHeadlessSelectField', (config) => fffHeadlessSelectField(config));
    });
}
