/**
 * @deprecated v3.1 — Filament selectFormComponent patch coordinator (fixtures/tests only).
 */
export {
    findTriggerLabelInOptions,
    populateRepositoryWithTriggerLabels,
    resolveTriggerLabel,
} from '../../../resources/js/components/select-field/select-field-trigger-labels.js';

export const FFF_SELECT_ATTACH_MAX_ATTEMPTS = 120;

export const FFF_SELECT_INNER_INSTANCE_KEY = '__fffSelectInnerInstance';

/**
 * @returns {() => void}
 */
export function bootSelectFieldPatches() {
    const applicator = globalThis.__fffSelectFieldPatchApplicator;

    if (typeof applicator === 'function') {
        return applicator();
    }

    return () => {};
}

export function createSelectFieldAttachFailureMessage({ statePath = 'unknown' } = {}, attempts = FFF_SELECT_ATTACH_MAX_ATTEMPTS) {
    return `Flex Fields Select coordinator failed to attach for state path "${statePath}" after ${attempts} attempts.`;
}

export function markSelectFieldShellAttached(shell, attached) {
    if (! shell?.dataset) {
        return;
    }

    shell.dataset.fffSelectAttached = attached ? 'true' : 'false';
}

function resolveInnerAlpineData(innerRoot) {
    if (! innerRoot) {
        return null;
    }

    if (typeof globalThis.Alpine !== 'undefined' && typeof globalThis.Alpine.$data === 'function') {
        return globalThis.Alpine.$data(innerRoot);
    }

    const stack = innerRoot._x_dataStack;

    if (! Array.isArray(stack) || stack.length === 0) {
        return null;
    }

    return stack[0] ?? null;
}

function resolveInnerSelectInstance(alpineData) {
    if (! alpineData) {
        return null;
    }

    if (alpineData[FFF_SELECT_INNER_INSTANCE_KEY]) {
        return alpineData[FFF_SELECT_INNER_INSTANCE_KEY];
    }

    if (alpineData.select) {
        return alpineData.select;
    }

    return null;
}

export default function fffSelectFieldCoordinator({ patchConfig = {} } = {}) {
    return {
        patchConfig,
        attached: false,
        attachAttempts: 0,
        attachFailureReported: false,
        maxAttachAttempts: FFF_SELECT_ATTACH_MAX_ATTEMPTS,
        detachPatches: null,
        _attachFrame: null,

        init() {
            this.attachToInnerSelect();
        },

        destroy() {
            if (this._attachFrame != null && typeof cancelAnimationFrame === 'function') {
                cancelAnimationFrame(this._attachFrame);
            }

            this.detachPatches?.();
            this.detachPatches = null;
        },

        reportAttachFailure(message) {
            if (this.attachFailureReported) {
                return;
            }

            this.attachFailureReported = true;
            console.error(message);
            this.$el?.dispatchEvent?.(new CustomEvent('fff-select-coordinator-attach-failed', { bubbles: true }));
            markSelectFieldShellAttached(this.$el, false);
        },

        attachToInnerSelect() {
            const attempt = () => {
                const innerRoot = this.$el?.querySelector?.('[data-fff-select-root]') ?? null;

                if (! innerRoot) {
                    this.attachAttempts += 1;

                    if (this.attachAttempts >= this.maxAttachAttempts) {
                        this.reportAttachFailure(
                            createSelectFieldAttachFailureMessage(this.patchConfig, this.maxAttachAttempts),
                        );

                        return;
                    }

                    this._attachFrame = requestAnimationFrame(attempt);

                    return;
                }

                const alpineData = resolveInnerAlpineData(innerRoot);
                const selectInstance = resolveInnerSelectInstance(alpineData);

                if (! selectInstance) {
                    this.attachAttempts += 1;

                    if (this.attachAttempts >= this.maxAttachAttempts) {
                        this.reportAttachFailure(
                            createSelectFieldAttachFailureMessage(this.patchConfig, this.maxAttachAttempts),
                        );

                        return;
                    }

                    this._attachFrame = requestAnimationFrame(attempt);

                    return;
                }

                this.detachPatches = bootSelectFieldPatches();
                this.attached = true;
                markSelectFieldShellAttached(this.$el, true);
                this.$el?.dispatchEvent?.(new CustomEvent('fff-select-coordinator-attached', { bubbles: true }));
            };

            attempt();
        },
    };
}
