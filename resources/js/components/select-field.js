/**
 * SelectField Alpine coordinator — patches Filament selectFormComponent instances.
 */
export function bootSelectFieldPatches(select, alpine, config) {
    const { $nextTick } = alpine
    const isInlineSearch = config.isInlineSearch;
    const isGridLayout = config.isGridLayout;
    const useRichListTriggerDisplay = config.useRichListTriggerDisplay;
    const useRichListDropdownLayout = config.useRichListDropdownLayout;
    const dropdownAlign = config.dropdownAlign;
    const fieldLabel = config.fieldLabel;

    const resolveTriggerLabel = (option) => {
        if (option.triggerLabel !== undefined) {
            return option.triggerLabel;
        }

        return option.label;
    };

    const findTriggerLabelInOptions = (value, options) => {
        const key = String(value);

        if (! options || ! Array.isArray(options)) {
            return null;
        }

        for (const option of options) {
            if (option.options && Array.isArray(option.options)) {
                const found = findTriggerLabelInOptions(value, option.options);

                if (found !== null) {
                    return found;
                }

                continue;
            }

            if (String(option.value) === key) {
                return resolveTriggerLabel(option);
            }
        }

        return null;
    };

    const populateRepositoryWithTriggerLabels = (select, options) => {
        if (! options || ! Array.isArray(options)) {
            return;
        }

        for (const option of options) {
            if (option.options && Array.isArray(option.options)) {
                populateRepositoryWithTriggerLabels(select, option.options);

                continue;
            }

            if (option.value === undefined) {
                continue;
            }

            select.labelRepository[option.value] = resolveTriggerLabel(option);
        }
    };

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
        if (! isInlineSearch || ! select?.searchInput || ! select?.searchContainer) {
            return null;
        }

        const { searchContainer, searchInput, selectButton } = select;

        searchContainer.classList.add('fff-select-inline-search-ctn');
        selectButton.insertBefore(searchContainer, select.selectedDisplay.nextSibling);

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
        if (! useRichListTriggerDisplay || ! select?.dropdown || ! select?.selectButton) {
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
        if (isGridLayout || useRichListTriggerDisplay || ! select?.dropdown || ! select?.selectButton) {
            return;
        }

        const dropdown = select.dropdown;
        const buttonWidth = select.selectButton.offsetWidth;
        const viewportCap = Math.max(buttonWidth, window.innerWidth - 32);

        dropdown.style.width = 'auto';
        dropdown.style.minWidth = `${buttonWidth}px`;
        dropdown.style.maxWidth = `${viewportCap}px`;

        const measuredWidth = Math.ceil(dropdown.scrollWidth);
        const targetWidth = Math.min(Math.max(buttonWidth, measuredWidth), viewportCap);

        dropdown.style.width = `${targetWidth}px`;
        dropdown.style.minWidth = `${buttonWidth}px`;
    };

    const patchGridResizeListener = (select) => {
        if (! isGridLayout || select.__fffGridResizePatched) {
            return;
        }

        select.__fffGridResizePatched = true;

        if (select.resizeListener) {
            window.removeEventListener('resize', select.resizeListener);
        }

        select.resizeListener = () => {
            applyGridDropdownWidth(select);
            select.positionDropdown();
        };

        window.addEventListener('resize', select.resizeListener);
    };

    const readDropdownGapPx = (wrapper) => {
        if (wrapper?.__fffDropdownGapPx !== undefined) {
            return wrapper.__fffDropdownGapPx;
        }

        const gap = wrapper
            ? getComputedStyle(wrapper).getPropertyValue('--fff-select-dropdown-gap').trim()
            : '0.5rem';

        const probe = document.createElement('div');
        probe.style.position = 'absolute';
        probe.style.visibility = 'hidden';
        probe.style.pointerEvents = 'none';
        probe.style.height = gap || '0.5rem';
        probe.style.width = '0';

        (wrapper ?? document.body).appendChild(probe);

        const pixels = probe.offsetHeight;

        probe.remove();

        if (wrapper) {
            wrapper.__fffDropdownGapPx = pixels;
        }

        return pixels;
    };

    const applyQuickPortaledPosition = (select) => {
        const dropdown = select?.dropdown;
        const button = select?.selectButton;

        if (! dropdown || ! button) {
            return;
        }

        const wrapper = resolveSelectWrapper(select);
        const gap = readDropdownGapPx(wrapper);
        const buttonRect = button.getBoundingClientRect();
        const viewportPadding = 5;
        const viewportWidth = window.innerWidth;
        const opensAbove = resolveDropdownOpensAbove(
            select,
            buttonRect,
            dropdown.offsetHeight || 0,
            gap,
        );

        dropdown.style.position = 'fixed';
        dropdown.style.margin = '0';
        dropdown.style.left = `${Math.max(viewportPadding, Math.min(buttonRect.left, viewportWidth - Math.max(buttonRect.width, dropdown.offsetWidth) - viewportPadding))}px`;
        dropdown.style.right = 'auto';
        dropdown.style.minWidth = `${buttonRect.width}px`;
        dropdown.style.top = opensAbove
            ? `${buttonRect.top - (dropdown.offsetHeight || 0) - gap}px`
            : `${buttonRect.bottom + gap}px`;

        dropdown.classList.toggle('fff-select-dropdown-panel--above', opensAbove);
        dropdown.classList.toggle('fff-select-dropdown-panel--below', ! opensAbove);
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

        ['sm', 'md', 'lg'].forEach((size) => {
            dropdown.classList.toggle(
                `fff-select-dropdown-panel--${size}`,
                wrapper.classList.contains(`fff-select-field--${size}`),
            );
        });

        [
            '--fff-select-focus',
            '--fff-select-grid-selected-label',
            '--fff-select-grid-ring-bg',
            '--fff-select-menu-hover',
            '--fff-select-menu-selected',
        ].forEach((name) => {
            const value = getComputedStyle(wrapper).getPropertyValue(name).trim();

            if (value !== '') {
                dropdown.style.setProperty(name, value);
            }
        });

        const isUserSelect = wrapper.classList.contains('fff-user-select');
        dropdown.classList.toggle('fff-select-dropdown-panel--user-select', isUserSelect);

        if (isUserSelect) {
            [
                '--fff-user-select-avatar-size',
            ].forEach((name) => {
                const value = getComputedStyle(wrapper).getPropertyValue(name).trim();

                if (value !== '') {
                    dropdown.style.setProperty(name, value);
                }
            });
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

    const revealDropdownPanel = (select) => {
        const dropdown = select?.dropdown;

        if (! dropdown) {
            return;
        }

        cancelDropdownCloseAnimation(select);
        dropdown.classList.remove('is-closing');
        dropdown.style.removeProperty('opacity');
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
                patchGridResizeListener(select);
                applyGridDropdownWidth(select);
            }

            if (shouldAlignDropdownEnd(select)) {
                finalizePortaledDropdownLayout(select, { alignEnd: true });

                return;
            }

            if (applyGridDropdownPosition(select)) {
                return;
            }

            if (isPortaledDropdown(select)) {
                finalizePortaledDropdownLayout(select);

                return;
            }

            select.__fffOriginalPositionDropdown?.call(select);
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

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (reducedMotion) {
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
        if (! select || select.__fffExclusiveDropdownBound) {
            return;
        }

        const overlays = window.Alpine?.store?.('fffOverlays');

        if (! overlays) {
            return;
        }

        select.__fffExclusiveDropdownBound = true;

        const ownerId = overlays.resolveOwnerId(select, 'fff-select');

        select.__fffDropdownOwnerId = ownerId;

        if (! ownerId) {
            return;
        }

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
        if (! select?.documentClickListener || select.__fffDocumentClickPatched) {
            return;
        }

        select.__fffDocumentClickPatched = true;

        document.removeEventListener('click', select.documentClickListener);

        const originalDocumentClickListener = select.documentClickListener;

        select.documentClickListener = (event) => {
            if (select.dropdown?.contains(event.target)) {
                return;
            }

            originalDocumentClickListener(event);
        };

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

    const applyPortaledDropdownPosition = (select, { alignEnd = false } = {}) => {
        const dropdown = select?.dropdown;
        const button = select?.selectButton;

        if (! dropdown || ! button || ! select.isOpen) {
            return false;
        }

        const wrapper = resolveSelectWrapper(select);
        const gap = readDropdownGapPx(wrapper);
        const buttonRect = button.getBoundingClientRect();
        const viewportPadding = 5;
        const viewportWidth = window.innerWidth;

        const dropdownWidth = dropdown.offsetWidth;
        const dropdownHeight = dropdown.offsetHeight;
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

        [
            'position',
            'top',
            'left',
            'right',
            'bottom',
            'margin',
            'min-width',
            'max-width',
            'width',
        ].forEach((property) => {
            dropdown.style.removeProperty(property);
        });
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

        if (! dropdown || dropdown.dataset.fffGlassApplied === 'true') {
            return;
        }

        const wrapper = resolveSelectWrapper(select);
        const styles = wrapper ? getComputedStyle(wrapper) : null;

        const readVar = (name, fallback) => {
            const value = styles?.getPropertyValue(name).trim();

            return value !== '' ? value : fallback;
        };

        dropdown.style.setProperty('background', readVar('--fff-select-menu-bg', 'rgb(255 255 255 / 0.9)'), 'important');
        dropdown.style.setProperty('backdrop-filter', readVar('--fff-select-menu-blur', 'blur(16px) saturate(180%)'), 'important');
        dropdown.style.setProperty('-webkit-backdrop-filter', readVar('--fff-select-menu-blur', 'blur(16px) saturate(180%)'), 'important');
        dropdown.style.setProperty('border', 'none', 'important');
        dropdown.style.setProperty(
            'box-shadow',
            readVar(
                '--fff-select-menu-shadow',
                '0 2px 8px 0 #0000000f, 0 -6px 12px 0 #00000008, 0 14px 28px 0 #00000014',
            ),
            'important',
        );
        dropdown.style.setProperty('border-radius', readVar('--fff-select-menu-radius', '1.5rem'), 'important');
        dropdown.style.setProperty('z-index', '20', 'important');
        dropdown.dataset.fffGlassApplied = 'true';
    };

    const clearIconHtml = config.clearIconHtml;
    const selectedOptionCheckIconHtml = config.selectedOptionCheckIconHtml;

    const patchSelectedOptionCheckIcon = (select) => {
        if (! selectedOptionCheckIconHtml || select.__fffSelectedOptionCheckPatched) {
            return;
        }

        select.__fffSelectedOptionCheckPatched = true;

        const originalCreateOptionElement = select.createOptionElement.bind(select);

        select.createOptionElement = function (value, label) {
            const option = originalCreateOptionElement(value, label);

            if (! option.classList.contains('fi-selected')) {
                return option;
            }

            const labelSpan = option.querySelector(':scope > span');

            if (! labelSpan || labelSpan.classList.contains('fff-select-option-selected-row')) {
                return option;
            }

            const labelContent = this.isHtmlAllowed
                ? labelSpan.innerHTML
                : labelSpan.textContent;

            labelSpan.innerHTML = '';
            labelSpan.classList.add('fff-select-option-selected-row');

            const labelWrapper = document.createElement('span');
            labelWrapper.className = 'fff-select-option-selected-row__label';

            if (this.isHtmlAllowed) {
                labelWrapper.innerHTML = labelContent;
            } else {
                labelWrapper.textContent = labelContent;
            }

            const check = document.createElement('span');
            check.className = 'fff-select-option-selected-check';
            check.setAttribute('aria-hidden', 'true');
            check.innerHTML = selectedOptionCheckIconHtml;

            labelSpan.appendChild(labelWrapper);
            labelSpan.appendChild(check);

            return option;
        };
    };

    const patchClearButtonIcon = (select) => {
        if (! clearIconHtml) {
            return;
        }

        const apply = () => {
            const isDisabled = resolveSelectWrapper(select)?.classList.contains('fi-disabled') ?? false;

            select.container?.querySelectorAll('.fi-select-input-value-remove-btn').forEach((button) => {
                if (isDisabled) {
                    button.style.display = 'none';

                    return;
                }

                button.style.display = '';
                button.innerHTML = clearIconHtml;
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
        patchSelectedOptionCheckIcon(select);
        if (config.shouldPatchUserSelectClient) {
            select.initialSelectedUserEntries = config.initialSelectedUserEntries;
            select.__fffMinSearchLength = config.userSelectMinSearchLength;
            patchUserSelectClient(select);
        }
        if (config.shouldPatchUserSelectMultiple) {
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
            cancelDropdownCloseAnimation(this);

            const willOpen = ! this.isOpen;

            if (willOpen) {
                announceSelectDropdownOpened(this);
            }

            this.__fffUseQuickPosition = true;

            const openPromise = originalOpenDropdown(...args);

            this.__fffUseQuickPosition = false;

            if (this.dropdown && this.isOpen) {
                portalDropdownToBody(this);
                syncDropdownPanelState(this);
                syncFocusOutlineOpenState(this, true);
                applyQuickPortaledPosition(this);
                revealDropdownPanel(this);
            }

            openPromise.then(() => {
                if (! this.dropdown || ! this.isOpen) {
                    return;
                }

                scheduleDropdownLayout(this);
                focusDropdownSearch(this);
            });
        };

        select.closeDropdown = function (...args) {
            syncFocusOutlineOpenState(this, false);

            const selectRef = this;

            if (! this.dropdown) {
                return originalCloseDropdown.apply(selectRef, args);
            }

            this.isOpen = false;
            this.selectButton?.setAttribute('aria-expanded', 'false');

            hideDropdownPanel(this, () => {
                restoreDropdownParent(selectRef);
                originalCloseDropdown.apply(selectRef, args);
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
                patchGridResizeListener(select);
                inlineSearch?.activate();
            };

            const closeDropdownWithGlass = select.closeDropdown.bind(select);

            select.closeDropdown = function (...args) {
                inlineSearch?.deactivate();

                return closeDropdownWithGlass(...args);
            };

            if (inlineSearch) {
                select.selectButton.addEventListener('click', () => {
                    if (select.isOpen) {
                        inlineSearch.activate();
                    }
                });
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

    const bootSelectFieldPatches = async () => {
        if (! select) {
            requestAnimationFrame(bootSelectFieldPatches);

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

    $nextTick(bootSelectFieldPatches);

    return () => {
        destroyUserSelectMultiple?.();
    };

}

export default function selectFieldPreload() {
    if (typeof window !== 'undefined') {
        window.bootSelectFieldPatches = bootSelectFieldPatches
    }

    return {
        init() {
            window.bootSelectFieldPatches = bootSelectFieldPatches
        },
    }
}
