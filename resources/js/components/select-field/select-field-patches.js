import { applyTeleportedMenuTheme } from '../../core/searchable-select-menu.js';
import {
    prefersReducedMotion,
    resolveTeleportedMenuZIndex,
    resolveThemeFingerprint,
} from '../../core/theme-utils.js';
import {
    bumpSelectCloseToken,
    shouldCommitDeferredClose,
} from './select-field-close-guard.js';
import {
    patchSelectFieldSelectionUx,
    syncKnownSelectedFromState,
} from './select-field-selection-ux.js';
import {
    findTriggerLabelInOptions,
    populateRepositoryWithTriggerLabels,
} from './select-field-trigger-labels.js';

const DROPDOWN_TOKEN_NAMES = Object.freeze([
    '--fff-select-focus',
    '--fff-select-grid-selected-label',
    '--fff-select-grid-ring-bg',
    '--fff-select-menu-hover',
    '--fff-select-menu-selected',
])

const USER_SELECT_TOKEN_NAMES = Object.freeze([
    '--fff-user-select-avatar-size',
])

const FIELD_SIZE_TOKENS = Object.freeze(['sm', 'md', 'lg'])

const PORTAL_STYLE_PROPERTIES = Object.freeze([
    'position',
    'top',
    'left',
    'right',
    'bottom',
    'margin',
    'min-width',
    'max-width',
    'width',
])

const DEFAULT_MENU_SHADOW = '0 2px 8px 0 #0000000f, 0 -6px 12px 0 #00000008, 0 14px 28px 0 #00000014'
const DEFAULT_MENU_RADIUS = '1.5rem'
const DEFAULT_DROPDOWN_GAP = '0.5rem'

/** @type {number | null} */
let defaultDropdownGapPx = null

function readTokenCache(wrapper, tokenNames, cacheKey) {
    const themeKey = resolveThemeFingerprint()
    const cached = wrapper[cacheKey]

    if (cached?.__themeKey === themeKey) {
        return cached
    }

    const styles = getComputedStyle(wrapper)
    const cache = Object.create(null)

    for (let index = 0; index < tokenNames.length; index++) {
        const name = tokenNames[index]
        const value = styles.getPropertyValue(name).trim()

        if (value !== '') {
            cache[name] = value
        }
    }

    cache.__themeKey = themeKey
    wrapper[cacheKey] = cache

    return cache
}

function applyTokenCache(dropdown, cache) {
    for (const name in cache) {
        if (name === '__themeKey') {
            continue
        }

        dropdown.style.setProperty(name, cache[name])
    }
}

export function bootSelectFieldPatches(select, alpine, config) {
    const { $nextTick } = alpine
    const isInlineSearch = config.isInlineSearch;
    const isGridLayout = config.isGridLayout;
    const useRichListTriggerDisplay = config.useRichListTriggerDisplay;
    const useRichListDropdownLayout = config.useRichListDropdownLayout;
    const keepSelectedOptionsInDropdown = Boolean(config.keepSelectedOptionsInDropdown);
    const dropdownAlign = config.dropdownAlign;
    const fieldLabel = config.fieldLabel;

    const injectInlineFieldLabel = (select) => {
        if (! fieldLabel || ! select?.selectButton) {
            return;
        }

        const button = select.selectButton;

        if (button.querySelector('.fff-select-inline-field-label')) {
            return;
        }

        const labelElement = document.createElement('span');
        labelElement.className = 'fff-select-inline-field-label';
        labelElement.textContent = fieldLabel;

        button.insertBefore(labelElement, select.selectedDisplay);
        button.classList.add('fff-select-input-btn--inline-field-label');
    };

    const setupInlineSearch = (select) => {
        if (! isInlineSearch || ! select?.searchInput || ! select?.searchContainer || ! select?.selectButton) {
            return null;
        }

        const { searchContainer, searchInput, selectButton, selectedDisplay } = select;

        searchContainer.classList.add('fff-select-inline-search-ctn');

        if (selectedDisplay?.parentNode === selectButton) {
            selectButton.insertBefore(searchContainer, selectedDisplay.nextSibling);
        } else {
            selectButton.appendChild(searchContainer);
        }

        return {
            activate: () => {
                selectButton.classList.add('fi-select-input-btn--search-active');
                searchInput.focus();
            },
            deactivate: () => {
                selectButton.classList.remove('fi-select-input-btn--search-active');
                searchInput.value = '';
                select.searchQuery = '';
            },
        };
    };

    const applyGridDropdownWidth = (select) => {
        if (! isGridLayout || ! select?.dropdown) {
            return;
        }

        const dropdown = select.dropdown;

        dropdown.classList.add('fi-width-none');
        dropdown.style.setProperty('width', '22rem', 'important');
        dropdown.style.setProperty('max-width', 'min(22rem, calc(100vw - 2rem))', 'important');
        dropdown.style.setProperty('min-width', '22rem', 'important');
    };

    const applyRichListDropdownLayout = (select) => {
        if (! useRichListDropdownLayout || ! select?.dropdown || ! select?.selectButton) {
            return;
        }

        const dropdown = select.dropdown;
        const buttonWidth = select.selectButton.offsetWidth;

        dropdown.style.width = `${buttonWidth}px`;
        dropdown.style.minWidth = `${buttonWidth}px`;
        dropdown.style.maxWidth = `min(${buttonWidth}px, calc(100vw - 2rem))`;
        dropdown.style.overflowX = 'visible';
    };

    const applyPlainListDropdownWidth = (select) => {
        if (isGridLayout || useRichListDropdownLayout || ! select?.dropdown || ! select?.selectButton) {
            return;
        }

        const dropdown = select.dropdown;
        const buttonWidth = select.selectButton.offsetWidth;
        const viewportCap = Math.max(buttonWidth, window.innerWidth - 32);

        // Measure with unconstrained width in one frame, then commit final geometry.
        dropdown.style.minWidth = `${buttonWidth}px`;
        dropdown.style.maxWidth = `${viewportCap}px`;
        dropdown.style.width = 'max-content';

        const measuredWidth = Math.ceil(dropdown.scrollWidth);
        const targetWidth = Math.min(Math.max(buttonWidth, measuredWidth), viewportCap);

        dropdown.style.width = `${targetWidth}px`;
    };

    const ensureGridResizeListener = (select) => {
        if (! isGridLayout || ! select) {
            return;
        }

        if (select.__fffGridResizeListener) {
            window.removeEventListener('resize', select.__fffGridResizeListener);
        }

        if (select.resizeListener && select.resizeListener !== select.__fffGridResizeListener) {
            window.removeEventListener('resize', select.resizeListener);
        }

        const listener = () => {
            if (! select.isOpen) {
                return;
            }

            applyGridDropdownWidth(select);
            select.positionDropdown();
        };

        select.__fffGridResizeListener = listener;
        select.resizeListener = listener;
        window.addEventListener('resize', listener);
    };

    const readDropdownGapPx = (wrapper) => {
        if (wrapper?.__fffDropdownGapPx !== undefined) {
            return wrapper.__fffDropdownGapPx;
        }

        if (! wrapper && defaultDropdownGapPx !== null) {
            return defaultDropdownGapPx;
        }

        const gap = wrapper
            ? getComputedStyle(wrapper).getPropertyValue('--fff-select-dropdown-gap').trim()
            : DEFAULT_DROPDOWN_GAP;

        const probe = document.createElement('div');
        probe.style.position = 'absolute';
        probe.style.visibility = 'hidden';
        probe.style.pointerEvents = 'none';
        probe.style.height = gap || DEFAULT_DROPDOWN_GAP;
        probe.style.width = '0';

        (wrapper ?? document.body).appendChild(probe);

        const pixels = probe.offsetHeight;

        probe.remove();

        if (wrapper) {
            wrapper.__fffDropdownGapPx = pixels;
        } else {
            defaultDropdownGapPx = pixels;
        }

        return pixels;
    };

    const applyQuickPortaledPosition = (select) => {
        const dropdown = select?.dropdown;
        const button = select?.selectButton;

        if (! dropdown || ! button) {
            return;
        }

        if (isGridLayout) {
            applyGridDropdownWidth(select);
        } else {
            const buttonWidth = button.getBoundingClientRect().width;

            dropdown.style.minWidth = `${buttonWidth}px`;
        }

        applyPortaledDropdownPosition(select, { requireOpen: false });
    };

    const resolveSelectWrapper = (select) => {
        return select?.selectButton?.closest('.fff-select-field')
            ?? select?.dropdown?.closest('.fff-select-field')
            ?? null;
    };

    const syncFocusOutlineOpenState = (select, isOpen) => {
        const wrapper = resolveSelectWrapper(select);

        if (! wrapper) {
            return;
        }

        wrapper.classList.toggle('is-dropdown-open', isOpen);
    };

    const hideInitialTriggerSsr = (select) => {
        const ssr = select?.selectButton
            ?.closest('.fi-select-input')
            ?.querySelector('.fff-select-trigger-ssr');

        if (ssr) {
            ssr.classList.add('is-replaced');
        }
    };

    const syncDropdownPanelState = (select) => {
        const dropdown = select?.dropdown;
        const wrapper = resolveSelectWrapper(select);

        if (! dropdown || ! wrapper) {
            return;
        }

        dropdown.classList.toggle('fff-select-dropdown-panel--layout-grid', isGridLayout);
        dropdown.classList.toggle('fff-select-dropdown-panel--layout-list', useRichListDropdownLayout);
        dropdown.classList.toggle(
            'fff-select-dropdown-panel--layout-plain',
            ! isGridLayout && ! useRichListDropdownLayout,
        );
        dropdown.classList.toggle('fi-width-none', isGridLayout);

        for (let index = 0; index < FIELD_SIZE_TOKENS.length; index++) {
            const size = FIELD_SIZE_TOKENS[index];

            dropdown.classList.toggle(
                `fff-select-dropdown-panel--${size}`,
                wrapper.classList.contains(`fff-select-field--${size}`),
            );
        }

        applyTokenCache(
            dropdown,
            readTokenCache(wrapper, DROPDOWN_TOKEN_NAMES, '__fffSelectDropdownTokenCache'),
        );

        const isUserSelect = wrapper.classList.contains('fff-user-select');
        dropdown.classList.toggle('fff-select-dropdown-panel--user-select', isUserSelect);

        if (isUserSelect) {
            applyTokenCache(
                dropdown,
                readTokenCache(wrapper, USER_SELECT_TOKEN_NAMES, '__fffUserSelectTokenCache'),
            );
        }
    };

    const focusDropdownSearch = (select) => {
        if (! select?.isSearchable || ! select?.searchInput || select?.isMultiple || ! select?.isOpen) {
            return;
        }

        requestAnimationFrame(() => {
            select.searchInput?.focus();
        });
    };

    const cancelDropdownCloseAnimation = (select) => {
        if (select.__fffDropdownCloseTimeout) {
            clearTimeout(select.__fffDropdownCloseTimeout);
            select.__fffDropdownCloseTimeout = null;
        }

        if (select.__fffDropdownCloseListener && select.dropdown) {
            select.dropdown.removeEventListener('transitionend', select.__fffDropdownCloseListener);
            select.__fffDropdownCloseListener = null;
        }
    };

    const freezeFilamentPositionListeners = (select) => {
        if (select.scrollListener) {
            window.removeEventListener('scroll', select.scrollListener);

            if (select.__fffFrozenScrollListener === undefined) {
                select.__fffFrozenScrollListener = select.scrollListener;
            }
        }

        if (select.resizeListener) {
            window.removeEventListener('resize', select.resizeListener);

            if (select.__fffFrozenResizeListener === undefined) {
                select.__fffFrozenResizeListener = select.resizeListener;
            }
        }

        if (select.__fffGridResizeListener) {
            window.removeEventListener('resize', select.__fffGridResizeListener);
        }
    };

    const revealDropdownPanel = (select) => {
        const dropdown = select?.dropdown;

        if (! dropdown) {
            return;
        }

        cancelDropdownCloseAnimation(select);
        dropdown.classList.remove('is-closing');
        dropdown.style.removeProperty('opacity');
        dropdown.style.removeProperty('display');
        dropdown.classList.remove('is-open');
        void dropdown.offsetWidth;

        requestAnimationFrame(() => {
            if (select.isOpen) {
                dropdown.classList.add('is-open');
            }
        });
    };

    const scheduleDropdownLayout = (select) => {
        if (! select?.dropdown || ! select.isOpen) {
            return;
        }

        requestAnimationFrame(() => {
            if (! select.isOpen || ! select.dropdown) {
                return;
            }

            applyDropdownGlassStyles(select);

            if (isGridLayout) {
                ensureGridResizeListener(select);
                applyGridDropdownWidth(select);
            }

            if (shouldAlignDropdownEnd(select)) {
                finalizePortaledDropdownLayout(select, { alignEnd: true });

                return;
            }

            if (applyGridDropdownPosition(select)) {
                syncDropdownScrollbarInset(select);

                return;
            }

            if (isPortaledDropdown(select)) {
                finalizePortaledDropdownLayout(select);

                return;
            }

            select.__fffOriginalPositionDropdown?.call(select);
            syncDropdownScrollbarInset(select);
        });
    };

    const hideDropdownPanel = (select, onHidden) => {
        const dropdown = select?.dropdown;

        if (! dropdown) {
            onHidden?.();

            return;
        }

        cancelDropdownCloseAnimation(select);
        dropdown.classList.remove('is-open');

        if (prefersReducedMotion()) {
            dropdown.classList.remove('is-closing');
            onHidden?.();

            return;
        }

        dropdown.classList.add('is-closing');

        let finished = false;

        const complete = () => {
            if (finished) {
                return;
            }

            finished = true;
            cancelDropdownCloseAnimation(select);
            dropdown.classList.remove('is-closing');
            onHidden?.();
        };

        select.__fffDropdownCloseListener = (event) => {
            if (event.target !== dropdown) {
                return;
            }

            if (event.propertyName === 'opacity' || event.propertyName === 'transform') {
                complete();
            }
        };

        dropdown.addEventListener('transitionend', select.__fffDropdownCloseListener);
        select.__fffDropdownCloseTimeout = window.setTimeout(complete, 180);
    };

    const bindExclusiveSelectDropdown = (select) => {
        if (! select || select.__fffUnbindExclusiveDropdown) {
            return;
        }

        const overlays = window.Alpine?.store?.('fffOverlays');

        if (! overlays || typeof overlays.resolveOwnerId !== 'function' || typeof overlays.register !== 'function') {
            return;
        }

        const ownerId = overlays.resolveOwnerId(select, 'fff-select');

        if (! ownerId) {
            return;
        }

        select.__fffDropdownOwnerId = ownerId;

        const controller = {
            isOpen: () => Boolean(select.isOpen),
            close: () => {
                if (typeof select.closeDropdown === 'function') {
                    select.closeDropdown();
                } else {
                    select.isOpen = false;
                }
            },
        };

        select.__fffUnbindExclusiveDropdown = overlays.register(ownerId, controller);
    };

    const announceSelectDropdownOpened = (select) => {
        if (! select?.__fffDropdownOwnerId) {
            return;
        }

        window.Alpine?.store?.('fffOverlays')?.open?.(select.__fffDropdownOwnerId);
    };

    const patchDocumentClickListener = (select) => {
        if (! select?.documentClickListener || select.documentClickListener.__fffPatched) {
            return;
        }

        document.removeEventListener('click', select.documentClickListener);

        const originalDocumentClickListener = select.documentClickListener;

        select.__fffOriginalDocumentClickListener = originalDocumentClickListener;

        const patched = (event) => {
            const target = event.target;

            if (target instanceof Node && select.dropdown?.contains(target)) {
                return;
            }

            originalDocumentClickListener(event);
        };

        patched.__fffPatched = true;
        select.documentClickListener = patched;
        document.addEventListener('click', select.documentClickListener);
    };

    const isPortaledDropdown = (select) => select?.dropdown?.parentNode === document.body;

    const resolveDropdownOpensAbove = (select, buttonRect, dropdownHeight, gap) => {
        const viewportPadding = 5;

        if (select.position === 'top') {
            return true;
        }

        if (select.position === 'bottom') {
            return false;
        }

        const spaceBelow = window.innerHeight - buttonRect.bottom - viewportPadding;
        const spaceAbove = buttonRect.top - viewportPadding;
        const needed = dropdownHeight + gap;

        if (spaceBelow >= needed) {
            return false;
        }

        if (spaceAbove >= needed) {
            return true;
        }

        return spaceAbove > spaceBelow;
    };

    const applyPortaledDropdownPosition = (select, { alignEnd = false, requireOpen = true } = {}) => {
        const dropdown = select?.dropdown;
        const button = select?.selectButton;

        if (! dropdown || ! button || (requireOpen && ! select.isOpen)) {
            return false;
        }

        const wrapper = resolveSelectWrapper(select);
        const gap = readDropdownGapPx(wrapper);
        const buttonRect = button.getBoundingClientRect();
        const viewportPadding = 5;
        const viewportWidth = window.innerWidth;

        const dropdownWidth = dropdown.offsetWidth;
        const measuredHeight = dropdown.offsetHeight;
        const dropdownHeight = measuredHeight > 0
            ? measuredHeight
            : Math.min(
                Math.max(
                    dropdown.querySelectorAll('.fi-select-input-option').length * 40,
                    120,
                ),
                224,
            );
        const opensAbove = resolveDropdownOpensAbove(select, buttonRect, dropdownHeight, gap);

        dropdown.style.position = 'fixed';
        dropdown.style.margin = '0';

        if (alignEnd) {
            dropdown.style.left = 'auto';
            dropdown.style.right = `${Math.max(viewportPadding, viewportWidth - buttonRect.right)}px`;

            if (buttonRect.right - dropdownWidth < viewportPadding) {
                dropdown.style.right = 'auto';
                dropdown.style.left = `${viewportPadding}px`;
            }
        } else {
            let left = buttonRect.left;

            left = Math.max(
                viewportPadding,
                Math.min(left, viewportWidth - dropdownWidth - viewportPadding),
            );

            dropdown.style.left = `${left}px`;
            dropdown.style.right = 'auto';
        }

        dropdown.style.top = opensAbove
            ? `${buttonRect.top - dropdownHeight - gap}px`
            : `${buttonRect.bottom + gap}px`;

        dropdown.classList.toggle('fff-select-dropdown-panel--above', opensAbove);
        dropdown.classList.toggle('fff-select-dropdown-panel--below', ! opensAbove);

        return true;
    };

    const applyGridDropdownPosition = (select) => {
        if (! isGridLayout) {
            return false;
        }

        applyGridDropdownWidth(select);

        return applyPortaledDropdownPosition(select);
    };

    const shouldAlignDropdownEnd = (select) => {
        return dropdownAlign === 'end'
            && select?.dropdown
            && select?.selectButton
            && select.isOpen;
    };

    const finalizePortaledDropdownLayout = (select, { alignEnd = false } = {}) => {
        if (! isPortaledDropdown(select)) {
            return;
        }

        applyRichListDropdownLayout(select);
        applyPlainListDropdownWidth(select);
        applyPortaledDropdownPosition(select, { alignEnd });
        syncDropdownScrollbarInset(select);
    };

    const resolveOptionsScrollContainer = (dropdown) => {
        if (! dropdown) {
            return null;
        }

        return dropdown.querySelector('.fi-select-input-options-ctn')
            ?? dropdown.querySelector('.fi-dropdown-list');
    };

    /**
     * Scrollbar-edge inset CSS must only apply when the options list overflows.
     * Short menus (e.g. Status) keep equal L/R gutters without phantom right padding.
     */
    const syncDropdownScrollbarInset = (select) => {
        const dropdown = select?.dropdown;

        if (! dropdown) {
            return;
        }

        const list = resolveOptionsScrollContainer(dropdown);

        if (! list) {
            dropdown.classList.remove('fff-select-dropdown-panel--scrollable');

            return;
        }

        dropdown.classList.remove('fff-select-dropdown-panel--scrollable');

        const hasOverflow = list.scrollHeight > list.clientHeight + 1;

        dropdown.classList.toggle('fff-select-dropdown-panel--scrollable', hasOverflow);
    };

    const patchOptionsOverflowSync = (select) => {
        if (! select || select.__fffOptionsOverflowPatched) {
            return;
        }

        select.__fffOptionsOverflowPatched = true;

        if (typeof select.renderOptions === 'function') {
            const originalRenderOptions = select.renderOptions.bind(select);

            select.renderOptions = function (...args) {
                const result = originalRenderOptions(...args);

                if (this.isOpen) {
                    requestAnimationFrame(() => {
                        syncDropdownScrollbarInset(this);
                    });
                }

                return result;
            };
        }
    };

    const portalDropdownToBody = (select) => {
        const dropdown = select?.dropdown;

        if (! dropdown || dropdown.parentNode === document.body) {
            return;
        }

        if (! select.__fffDropdownOriginalParent) {
            select.__fffDropdownOriginalParent = dropdown.parentNode;
        }

        document.body.appendChild(dropdown);
    };

    const clearPortaledDropdownStyles = (dropdown) => {
        if (! dropdown) {
            return;
        }

        for (let index = 0; index < PORTAL_STYLE_PROPERTIES.length; index++) {
            dropdown.style.removeProperty(PORTAL_STYLE_PROPERTIES[index]);
        }

        dropdown.style.removeProperty('box-shadow');
        dropdown.style.removeProperty('border');
        dropdown.style.removeProperty('border-radius');
        dropdown.style.removeProperty('z-index');
        dropdown.style.removeProperty('background');
        dropdown.style.removeProperty('background-color');
        dropdown.style.removeProperty('backdrop-filter');
        dropdown.style.removeProperty('-webkit-backdrop-filter');
        dropdown.style.removeProperty('color');
        dropdown.__fffGlassThemeKey = null;
        dropdown.__fffMenuThemeKey = null;
    };

    const restoreDropdownParent = (select) => {
        const dropdown = select?.dropdown;
        const parent = select?.__fffDropdownOriginalParent;

        if (! dropdown || ! parent || dropdown.parentNode === parent) {
            return;
        }

        clearPortaledDropdownStyles(dropdown);
        parent.appendChild(dropdown);
    };

    const applyDropdownGlassStyles = (select) => {
        const dropdown = select?.dropdown;

        if (! dropdown) {
            return;
        }

        dropdown.classList.add('fff-teleported-menu');
        applyTeleportedMenuTheme(dropdown);

        const themeKey = resolveThemeFingerprint();

        if (dropdown.__fffGlassThemeKey === themeKey) {
            dropdown.style.setProperty('z-index', resolveTeleportedMenuZIndex(), 'important');

            return;
        }

        const wrapper = resolveSelectWrapper(select);
        let shadow = DEFAULT_MENU_SHADOW;
        let radius = DEFAULT_MENU_RADIUS;

        if (wrapper) {
            const styles = getComputedStyle(wrapper);
            const shadowValue = styles.getPropertyValue('--fff-select-menu-shadow').trim();
            const radiusValue = styles.getPropertyValue('--fff-select-menu-radius').trim();

            if (shadowValue !== '') {
                shadow = shadowValue;
            }

            if (radiusValue !== '') {
                radius = radiusValue;
            }
        }

        dropdown.style.setProperty('box-shadow', shadow, 'important');
        dropdown.style.setProperty('border', 'none', 'important');
        dropdown.style.setProperty('border-radius', radius, 'important');
        dropdown.style.setProperty('z-index', resolveTeleportedMenuZIndex(), 'important');
        dropdown.__fffGlassThemeKey = themeKey;
    };

    const clearIconHtml = config.clearIconHtml;
    const selectedOptionCheckIconHtml = config.selectedOptionCheckIconHtml;

    const patchClearButtonIcon = (select) => {
        if (! clearIconHtml) {
            return;
        }

        const apply = () => {
            const isDisabled = resolveSelectWrapper(select)?.classList.contains('fi-disabled') ?? false;

            select.container?.querySelectorAll('.fi-select-input-value-remove-btn').forEach((button) => {
                if (isDisabled) {
                    button.hidden = true;
                    button.setAttribute('aria-hidden', 'true');

                    return;
                }

                button.hidden = false;
                button.removeAttribute('aria-hidden');

                if (button.dataset.fffClearIconApplied === '1') {
                    return;
                }

                button.innerHTML = clearIconHtml;
                button.dataset.fffClearIconApplied = '1';
            });
        };

        const originalUpdateSelectedDisplay = select.updateSelectedDisplay.bind(select);

        select.updateSelectedDisplay = async function (...args) {
            const result = await originalUpdateSelectedDisplay(...args);

            apply();

            return result;
        };

        apply();
    };

    let destroyUserSelectMultiple = null;

    const applySelectFieldPatches = (select) => {
        if (! select || select.__fffPatchesApplied) {
            return;
        }

        select.__fffPatchesApplied = true;

        patchClearButtonIcon(select);
        patchSelectFieldSelectionUx(select, {
            isGridLayout,
            keepSelectedOptionsInDropdown,
            selectedOptionCheckIconHtml,
        });
        patchOptionsOverflowSync(select);
        if (config.shouldPatchUserSelectClient && typeof patchUserSelectClient === 'function') {
            select.initialSelectedUserEntries = config.initialSelectedUserEntries;
            select.__fffMinSearchLength = config.userSelectMinSearchLength;
            patchUserSelectClient(select);
        }
        if (config.shouldPatchUserSelectMultiple && typeof patchUserSelectMultiple === 'function') {
            destroyUserSelectMultiple = patchUserSelectMultiple(select);
        }
        patchDocumentClickListener(select);
        bindExclusiveSelectDropdown(select);

        if (select.dropdown) {
            select.dropdown.classList.add('fff-select-dropdown-panel');
            syncDropdownPanelState(select);
            applyDropdownGlassStyles(select);
        }

        const originalOpenDropdown = select.openDropdown.bind(select);
        const originalCloseDropdown = select.closeDropdown.bind(select);
        const originalPositionDropdown = select.positionDropdown.bind(select);

        select.__fffOriginalPositionDropdown = originalPositionDropdown;

        select.positionDropdown = function (...args) {
            if (this.__fffUseQuickPosition) {
                if (applyGridDropdownPosition(this)) {
                    return;
                }

                applyQuickPortaledPosition(this);

                return;
            }

            if (shouldAlignDropdownEnd(this)) {
                if (isGridLayout) {
                    applyGridDropdownWidth(this);
                }

                finalizePortaledDropdownLayout(this, { alignEnd: true });

                return;
            }

            if (applyGridDropdownPosition(this)) {
                return;
            }

            if (isPortaledDropdown(this)) {
                finalizePortaledDropdownLayout(this);

                return;
            }

            originalPositionDropdown(...args);
        };

        select.openDropdown = async function (...args) {
            bumpSelectCloseToken(this);
            cancelDropdownCloseAnimation(this);
            this.dropdown?.classList.remove('is-closing');

            // Treat current selections as already drawn so reopen does not replay the check stroke.
            syncKnownSelectedFromState(this);
            bindExclusiveSelectDropdown(this);
            patchDocumentClickListener(this);

            const willOpen = ! this.isOpen;

            if (willOpen) {
                announceSelectDropdownOpened(this);
            }

            this.__fffUseQuickPosition = true;

            let openPromise;

            try {
                openPromise = Promise.resolve(originalOpenDropdown(...args));
            } finally {
                this.__fffUseQuickPosition = false;
            }

            if (this.dropdown && this.isOpen) {
                portalDropdownToBody(this);
                syncDropdownPanelState(this);
                syncFocusOutlineOpenState(this, true);
                applyQuickPortaledPosition(this);
                revealDropdownPanel(this);
                syncDropdownScrollbarInset(this);
            }

            try {
                await openPromise;
            } catch (error) {
                syncFocusOutlineOpenState(this, false);

                throw error;
            }

            if (! this.dropdown || ! this.isOpen) {
                return;
            }

            scheduleDropdownLayout(this);
            focusDropdownSearch(this);
        };

        select.closeDropdown = function (...args) {
            syncFocusOutlineOpenState(this, false);

            const selectRef = this;

            if (! this.dropdown) {
                return originalCloseDropdown.apply(selectRef, args);
            }

            const closeToken = bumpSelectCloseToken(this);

            this.isOpen = false;
            this.selectButton?.setAttribute('aria-expanded', 'false');
            freezeFilamentPositionListeners(this);

            hideDropdownPanel(this, () => {
                if (! shouldCommitDeferredClose(selectRef, closeToken)) {
                    return;
                }

                restoreDropdownParent(selectRef);
                originalCloseDropdown.apply(selectRef, args);
                selectRef.__fffFrozenScrollListener = undefined;
                selectRef.__fffFrozenResizeListener = undefined;
            });
        };

        if (! useRichListTriggerDisplay) {
            select.populateLabelRepositoryFromOptions = function (options) {
                populateRepositoryWithTriggerLabels(this, options);
            };

            const originalGetSelectedOptionLabel = select.getSelectedOptionLabel.bind(select);

            select.getSelectedOptionLabel = function (value) {
                const triggerLabel = findTriggerLabelInOptions(value, this.options);

                if (triggerLabel !== null) {
                    this.labelRepository[value] = triggerLabel;

                    return triggerLabel;
                }

                return originalGetSelectedOptionLabel(value);
            };

            const originalGetSelectedOptionLabels = select.getSelectedOptionLabels.bind(select);

            select.getSelectedOptionLabels = function () {
                const labels = originalGetSelectedOptionLabels();

                for (const value of Object.keys(labels)) {
                    const triggerLabel = findTriggerLabelInOptions(value, this.options);

                    if (triggerLabel !== null) {
                        labels[value] = triggerLabel;
                    }
                }

                return labels;
            };

            const originalGetLabelForSingleSelection = select.getLabelForSingleSelection.bind(select);

            select.getLabelForSingleSelection = async function () {
                const triggerLabel = findTriggerLabelInOptions(this.state, this.options);

                if (triggerLabel !== null) {
                    this.labelRepository[this.state] = triggerLabel;

                    return triggerLabel;
                }

                return originalGetLabelForSingleSelection();
            };

            const originalUpdateSelectedDisplay = select.updateSelectedDisplay.bind(select);

            select.updateSelectedDisplay = async function () {
                populateRepositoryWithTriggerLabels(this, this.options);

                return originalUpdateSelectedDisplay();
            };

            if (! select.__fffTriggerLabelsPrimed) {
                populateRepositoryWithTriggerLabels(select, select.options);
            }
        }

        injectInlineFieldLabel(select);

        const inlineSearch = setupInlineSearch(select);

        if (inlineSearch || isGridLayout) {
            const openDropdownWithGlass = select.openDropdown.bind(select);

            select.openDropdown = async function (...args) {
                await openDropdownWithGlass(...args);
                applyGridDropdownWidth(select);
                ensureGridResizeListener(select);
                inlineSearch?.activate();
            };

            const closeDropdownWithGlass = select.closeDropdown.bind(select);

            select.closeDropdown = function (...args) {
                inlineSearch?.deactivate();

                return closeDropdownWithGlass(...args);
            };

            if (inlineSearch) {
                const onInlineSearchClick = () => {
                    if (select.isOpen) {
                        inlineSearch.activate();
                    }
                };

                select.__fffInlineSearchClickHandler = onInlineSearchClick;
                select.selectButton.addEventListener('click', onInlineSearchClick);
            }
        }

        if (useRichListTriggerDisplay || select.__fffTriggerLabelsPrimed) {
            hideInitialTriggerSsr(select);
        }
    };

    const primeTriggerLabelDisplay = async (select) => {
        if (useRichListTriggerDisplay || ! select || select.__fffTriggerLabelsPrimed) {
            if (useRichListTriggerDisplay) {
                hideInitialTriggerSsr(select);
            }

            return;
        }

        populateRepositoryWithTriggerLabels(select, select.options);
        await select.updateSelectedDisplay();
        select.__fffTriggerLabelsPrimed = true;
        hideInitialTriggerSsr(select);
    };

    const waitForTriggerPaint = () => new Promise((resolve) => {
        requestAnimationFrame(() => requestAnimationFrame(resolve));
    });

    const runPatchBootSequence = async () => {
        if (! select) {
            return;
        }

        applySelectFieldPatches(select);

        if (config.isUserSelectField) {
            await select.updateSelectedDisplay();
            await waitForTriggerPaint();
            (window.__fffHideInitialTriggerSsr ?? hideInitialTriggerSsr)(select);
        } else {
            await primeTriggerLabelDisplay(select);
        }
    };

    $nextTick(runPatchBootSequence);

    return () => {
        destroyUserSelectMultiple?.();
        destroyUserSelectMultiple = null;

        if (! select) {
            return;
        }

        bumpSelectCloseToken(select);
        cancelDropdownCloseAnimation(select);

        select.__fffUnbindExclusiveDropdown?.();
        select.__fffUnbindExclusiveDropdown = null;

        if (select.__fffGridResizeListener) {
            window.removeEventListener('resize', select.__fffGridResizeListener);

            if (select.resizeListener === select.__fffGridResizeListener) {
                select.resizeListener = null;
            }

            select.__fffGridResizeListener = null;
        }

        if (select.__fffInlineSearchClickHandler && select.selectButton) {
            select.selectButton.removeEventListener('click', select.__fffInlineSearchClickHandler);
            select.__fffInlineSearchClickHandler = null;
        }

        if (select.documentClickListener?.__fffPatched) {
            document.removeEventListener('click', select.documentClickListener);

            if (select.__fffOriginalDocumentClickListener) {
                select.documentClickListener = select.__fffOriginalDocumentClickListener;
                select.__fffOriginalDocumentClickListener = null;
            }
        }

        restoreDropdownParent(select);
        syncFocusOutlineOpenState(select, false);
        select.dropdown?.classList.remove(
            'fff-select-dropdown-panel--scrollable',
            'is-open',
            'is-closing',
        );
    };

}
