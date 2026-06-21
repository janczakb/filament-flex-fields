/**
 * SelectField Alpine coordinator — patches Filament selectFormComponent instances.
 */
export { bootSelectFieldPatches } from './select-field/select-field-patches.js';
export {
    findTriggerLabelInOptions,
    populateRepositoryWithTriggerLabels,
    resolveTriggerLabel,
} from './select-field/select-field-trigger-labels.js';

import { bootSelectFieldPatches } from './select-field/select-field-patches.js';

function resolveSelectFieldPatchApplicator() {
    if (typeof globalThis.__fffSelectFieldPatchApplicator === 'function') {
        return globalThis.__fffSelectFieldPatchApplicator;
    }

    return bootSelectFieldPatches;
}

/** Filament selectFormComponent exposes its Tom Select instance on Alpine data as `select`. */
export const FFF_SELECT_INNER_INSTANCE_KEY = 'select';

export const FFF_SELECT_ATTACH_MAX_ATTEMPTS = 120;

export function createSelectFieldAttachFailureMessage(patchConfig, attempts) {
    const field = patchConfig?.statePath ?? patchConfig?.fieldLabel ?? 'unknown select field';

    return `[filament-flex-fields] SelectField coordinator failed to attach patches for "${field}" after ${attempts} attempts. `
        + 'Ensure Filament select.js loaded and selectFormComponent initialized on [data-fff-select-root].';
}

export function markSelectFieldShellAttached(shell, attached) {
    if (! shell) {
        return;
    }

    shell.dataset.fffSelectAttached = attached ? 'true' : 'false';
}

export default function fffSelectFieldCoordinator({ patchConfig = {} } = {}) {
    return {
        patchConfig,
        detachPatches: null,
        attachAttempts: 0,
        maxAttachAttempts: FFF_SELECT_ATTACH_MAX_ATTEMPTS,
        attached: false,
        attachFailureReported: false,

        init() {
            markSelectFieldShellAttached(this.$el, false);

            this.$nextTick(() => {
                this.attachToInnerSelect();
            });
        },

        getInnerRoot() {
            return this.$el.querySelector('[data-fff-select-root]');
        },

        getInnerAlpineData() {
            const root = this.getInnerRoot();

            if (! root) {
                return null;
            }

            if (typeof Alpine !== 'undefined' && typeof Alpine.$data === 'function') {
                try {
                    return Alpine.$data(root);
                } catch {
                    // Fall back to legacy Alpine stack access.
                }
            }

            return root._x_dataStack?.[0] ?? null;
        },

        reportAttachFailure() {
            if (this.attachFailureReported) {
                return;
            }

            this.attachFailureReported = true;

            const message = createSelectFieldAttachFailureMessage(this.patchConfig, this.attachAttempts);

            console.error(message);

            this.$el.dispatchEvent(new CustomEvent('fff-select-coordinator-attach-failed', {
                bubbles: true,
                detail: {
                    patchConfig: this.patchConfig,
                    attempts: this.attachAttempts,
                    message,
                },
            }));
        },

        attachToInnerSelect() {
            const innerRoot = this.getInnerRoot();

            if (! innerRoot) {
                this.attachAttempts++;

                if (this.attachAttempts >= this.maxAttachAttempts) {
                    this.reportAttachFailure();

                    return;
                }

                requestAnimationFrame(() => {
                    this.attachToInnerSelect();
                });

                return;
            }

            const alpineData = this.getInnerAlpineData();
            const selectInstance = alpineData?.[FFF_SELECT_INNER_INSTANCE_KEY];

            if (! selectInstance) {
                this.attachAttempts++;

                if (this.attachAttempts >= this.maxAttachAttempts) {
                    this.reportAttachFailure();

                    return;
                }

                requestAnimationFrame(() => {
                    this.attachToInnerSelect();
                });

                return;
            }

            this.detachPatches = resolveSelectFieldPatchApplicator()(selectInstance, alpineData, this.patchConfig);
            this.attached = true;
            markSelectFieldShellAttached(this.$el, true);

            this.$el.dispatchEvent(new CustomEvent('fff-select-coordinator-attached', {
                bubbles: true,
                detail: {
                    patchConfig: this.patchConfig,
                    attempts: this.attachAttempts,
                },
            }));
        },

        destroy() {
            this.detachPatches?.();
            this.detachPatches = null;
            this.attached = false;
            markSelectFieldShellAttached(this.$el, false);
        },
    };
}

