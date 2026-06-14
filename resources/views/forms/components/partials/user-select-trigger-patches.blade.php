const patchUserSelectMultiple = (select) => {
    if (! select || select.__fffUserSelectMultiplePatched) {
        return;
    }

    select.__fffUserSelectMultiplePatched = true;

    const resolveTagsContainer = (selectInstance) => {
        return selectInstance.container
            ?.closest('.fff-select-field-wrapper')
            ?.querySelector('[data-fff-user-select-tags]');
    };

    const resolveInitialUserEntry = (selectInstance, value) => {
        const entries = selectInstance.initialSelectedUserEntries;

        if (! Array.isArray(entries)) {
            return null;
        }

        const key = String(value);

        return entries.find((entry) => String(entry?.value) === key) ?? null;
    };

    const primeInitialUserSelectLabels = (selectInstance) => {
        if (! Array.isArray(selectInstance.initialOptionLabels)) {
            return;
        }

        for (const option of selectInstance.initialOptionLabels) {
            if (option?.value === undefined || option?.label === undefined) {
                continue;
            }

            const key = String(option.value);

            if (! selectInstance.labelRepository[option.value]) {
                selectInstance.labelRepository[option.value] = option.label;
            }

            if (! selectInstance.labelRepository[key]) {
                selectInstance.labelRepository[key] = option.label;
            }

            if (option.user) {
                storeUserInRepository(selectInstance, option.value, option.user);
            }
        }
    };

    primeInitialUserSelectLabels(select);

    const buildUserMetaMap = (selectInstance) => {
        const meta = {};

        const assignMeta = (value, user) => {
            if (value === undefined || ! user) {
                return;
            }

            meta[String(value)] = user;
        };

        const walk = (options) => {
            if (! Array.isArray(options)) {
                return;
            }

            for (const option of options) {
                if (option.options && Array.isArray(option.options)) {
                    walk(option.options);

                    continue;
                }

                if (option.value === undefined) {
                    continue;
                }

                assignMeta(option.value, option.user ?? {
                    name: option.userName ?? String(option.value),
                    email: null,
                    avatarUrl: null,
                    verified: false,
                    initials: '',
                });
            }
        };

        walk(selectInstance.options);

        if (Array.isArray(selectInstance.initialOptionLabels)) {
            for (const option of selectInstance.initialOptionLabels) {
                if (option?.value === undefined) {
                    continue;
                }

                assignMeta(option.value, option.user ?? {
                    name: option.userName ?? option.label ?? String(option.value),
                    email: null,
                    avatarUrl: null,
                    verified: false,
                    initials: '',
                });
            }
        }

        if (Array.isArray(selectInstance.initialSelectedUserEntries)) {
            for (const entry of selectInstance.initialSelectedUserEntries) {
                if (entry?.value === undefined) {
                    continue;
                }

                assignMeta(entry.value, entry.user ?? {
                    name: String(entry.value),
                    email: null,
                    avatarUrl: null,
                    verified: false,
                    initials: '',
                });
            }
        }

        return meta;
    };

    const resolveUserEntries = async (selectInstance) => {
        const state = Array.isArray(selectInstance.state) ? selectInstance.state : [];
        const meta = buildUserMetaMap(selectInstance);

        return state.map((value) => {
            const key = String(value);
            const initialEntry = resolveInitialUserEntry(selectInstance, value);
            const user = initialEntry?.user
                ?? meta[key]
                ?? findUserInOptions(selectInstance, value)
                ?? {
                    name: key,
                    email: null,
                    avatarUrl: null,
                    verified: false,
                    initials: '',
                };

            storeUserInRepository(selectInstance, value, user);

            return {
                value: key,
                user,
            };
        });
    };

    const resolveTriggerNamesAvailableWidth = (selectInstance) => {
        const valueContainer = selectInstance.selectButton?.querySelector('.fi-select-input-value-ctn');

        if (valueContainer) {
            const width = valueContainer.getBoundingClientRect().width;

            if (width > 0) {
                return Math.max(0, width - 8);
            }
        }

        const button = selectInstance.selectButton;

        if (! button) {
            return 0;
        }

        const buttonWidth = button.getBoundingClientRect().width;
        let reserved = 24;

        button.querySelectorAll(
            '.fi-select-input-actions, .fi-select-input-chevron, .fi-select-input-value-remove-btn, .fff-select-inline-search-ctn',
        ).forEach((element) => {
            const width = element.getBoundingClientRect().width;

            if (width > 0) {
                reserved += width;
            }
        });

        return Math.max(0, buttonWidth - reserved - 16);
    };

    let namesMeasureCanvas = null;
    let namesMeasureContext = null;

    const measureTriggerTextWidth = (selectInstance, text) => {
        if (! namesMeasureCanvas) {
            namesMeasureCanvas = document.createElement('canvas');
            namesMeasureContext = namesMeasureCanvas.getContext('2d');
        }

        if (! namesMeasureContext) {
            return text.length * 8;
        }

        const reference = selectInstance.selectButton?.querySelector('.fff-user-select__trigger-names')
            ?? selectInstance.selectButton?.querySelector('.fi-select-input-value-label');

        if (reference) {
            const style = getComputedStyle(reference);
            namesMeasureContext.font = `${style.fontWeight} ${style.fontSize} ${style.fontFamily}`;
        } else {
            namesMeasureContext.font = '500 0.875rem ui-sans-serif, system-ui, sans-serif';
        }

        return namesMeasureContext.measureText(text).width;
    };

    const fitNamesSummary = (selectInstance, names, availableWidth) => {
        if (! Array.isArray(names) || names.length === 0) {
            return '';
        }

        if (names.length === 1) {
            return names[0];
        }

        if (availableWidth <= 0) {
            return names.join(', ');
        }

        const separator = ', ';
        let visibleText = names[0];
        let visibleCount = 1;

        if (measureTriggerTextWidth(selectInstance, visibleText) > availableWidth) {
            while (visibleText.length > 1 && measureTriggerTextWidth(selectInstance, visibleText + '…') > availableWidth) {
                visibleText = visibleText.slice(0, -1);
            }

            return visibleText.length > 0 ? visibleText + '…' : names[0].slice(0, 1) + '…';
        }

        for (let index = 1; index < names.length; index++) {
            const candidate = visibleText + separator + names[index];
            const hiddenCount = names.length - (index + 1);
            const candidateText = hiddenCount > 0 ? candidate + ' +' + hiddenCount : candidate;

            if (measureTriggerTextWidth(selectInstance, candidateText) > availableWidth) {
                break;
            }

            visibleText = candidate;
            visibleCount = index + 1;
        }

        const hidden = names.length - visibleCount;

        if (hidden > 0) {
            visibleText = visibleText + ' +' + hidden;
        }

        return visibleText;
    };

    const renderTriggerNames = (selectInstance, names) => {
        const labelContainer = document.createElement('span');
        labelContainer.className = 'fi-select-input-value-label';

        const namesElement = document.createElement('span');
        namesElement.className = 'fff-user-select__trigger-names';
        namesElement.dataset.fffUserSelectNames = names.join('\n');

        namesElement.textContent = fitNamesSummary(
            selectInstance,
            names,
            resolveTriggerNamesAvailableWidth(selectInstance),
        );
        labelContainer.appendChild(namesElement);

        return labelContainer;
    };

    const renderSingleUserTrigger = (user) => {
        const labelContainer = document.createElement('span');
        labelContainer.className = 'fi-select-input-value-label';
        labelContainer.innerHTML = renderUserOptionHtml(user, 'trigger');

        return labelContainer;
    };

    const createSelectedTag = (selectInstance, entry) => {
        const wrapper = document.createElement('span');
        wrapper.innerHTML = renderUserTagHtml(entry.value, entry.user, ! selectInstance.isDisabled);

        return wrapper.firstElementChild;
    };

    const renderSelectedTags = (selectInstance, entries) => {
        const container = resolveTagsContainer(selectInstance);

        if (! container) {
            return;
        }

        container.replaceChildren();

        if (! Array.isArray(entries) || entries.length < 2) {
            container.hidden = true;

            return;
        }

        container.hidden = false;

        entries.forEach((entry) => {
            const tag = createSelectedTag(selectInstance, entry);

            if (tag) {
                container.appendChild(tag);
            }
        });
    };

    const removeSelectedUser = (selectInstance, value) => {
        if (! selectInstance.isMultiple || selectInstance.isDisabled) {
            return false;
        }

        const currentState = Array.isArray(selectInstance.state) ? [...selectInstance.state] : [];
        const removeKey = String(value);
        const isSelected = currentState.some((item) => String(item) === removeKey);

        if (! isSelected) {
            return false;
        }

        const nextState = currentState.filter((item) => String(item) !== removeKey);

        selectInstance.state = nextState;

        if (Array.isArray(selectInstance.initialSelectedUserEntries)) {
            selectInstance.initialSelectedUserEntries = selectInstance.initialSelectedUserEntries.filter(
                (entry) => String(entry?.value) !== removeKey,
            );
        }

        selectInstance.onStateChange([...nextState]);

        if (selectInstance.livewireId && selectInstance.statePath) {
            Livewire.find(selectInstance.livewireId)?.$wire?.set(
                selectInstance.statePath,
                [...nextState],
            );
        }

        selectInstance.updateSelectedDisplay();
        selectInstance.renderOptions();

        if (selectInstance.isOpen) {
            selectInstance.deferPositionDropdown?.();
        }

        selectInstance.maintainFocusInMultipleMode?.();

        return true;
    };

    const bindTagRemoveHandlers = (selectInstance) => {
        const container = resolveTagsContainer(selectInstance);

        if (! container || container.__fffUserSelectTagsBound) {
            return;
        }

        container.__fffUserSelectTagsBound = true;

        container.addEventListener('click', (event) => {
            const button = event.target.closest('.fff-user-select__selected-tag-remove');

            if (! button) {
                return;
            }

            const tag = button.closest('.fff-user-select__selected-tag[data-value]');
            const value = tag?.dataset.value;

            if (value === undefined) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            removeSelectedUser(selectInstance, value);
        });
    };

    const renderMultipleTriggerDisplay = async (selectInstance, target = selectInstance.selectedDisplay) => {
        if (! selectInstance.isMultiple || ! target) {
            return;
        }

        const entries = await resolveUserEntries(selectInstance);

        target.replaceChildren();

        if (entries.length === 0) {
            renderSelectedTags(selectInstance, []);
            hideInitialTriggerSsr(selectInstance);

            return;
        }

        if (entries.length === 1) {
            target.appendChild(renderSingleUserTrigger(entries[0].user));
            renderSelectedTags(selectInstance, []);
            hideInitialTriggerSsr(selectInstance);

            return;
        }

        target.appendChild(renderTriggerNames(selectInstance, entries.map((entry) => entry.user.name)));
        renderSelectedTags(selectInstance, entries);
        hideInitialTriggerSsr(selectInstance);
        scheduleTriggerNamesReflow(selectInstance);
    };

    const scheduleTriggerNamesReflow = (selectInstance) => {
        if (! selectInstance.isMultiple) {
            return;
        }

        if (selectInstance.__fffUserSelectNamesReflowFrame) {
            cancelAnimationFrame(selectInstance.__fffUserSelectNamesReflowFrame);
        }

        selectInstance.__fffUserSelectNamesReflowFrame = requestAnimationFrame(() => {
            const namesElement = selectInstance.selectedDisplay?.querySelector('.fff-user-select__trigger-names');

            if (! namesElement) {
                return;
            }

            const names = (namesElement.dataset.fffUserSelectNames ?? '')
                .split('\n')
                .map((name) => name.trim())
                .filter((name) => name !== '');

            if (names.length < 2) {
                return;
            }

            namesElement.textContent = fitNamesSummary(
                selectInstance,
                names,
                resolveTriggerNamesAvailableWidth(selectInstance),
            );
        });
    };

    const observeTriggerNamesContainer = (selectInstance) => {
        const valueContainer = selectInstance.selectButton?.querySelector('.fi-select-input-value-ctn');

        if (! valueContainer || selectInstance.__fffUserSelectNamesResizeObserver) {
            return;
        }

        if (typeof ResizeObserver === 'undefined') {
            return;
        }

        selectInstance.__fffUserSelectNamesResizeObserver = new ResizeObserver(() => {
            scheduleTriggerNamesReflow(selectInstance);
        });

        selectInstance.__fffUserSelectNamesResizeObserver.observe(valueContainer);
    };

    bindTagRemoveHandlers(select);

    if (! select.__fffUserSelectResizeListener) {
        select.__fffUserSelectResizeListener = () => scheduleTriggerNamesReflow(select);
        window.addEventListener('resize', select.__fffUserSelectResizeListener);
    }

    select.addBadgesForSelectedOptions = function () {
        // UserSelect multiple display is rendered in updateSelectedDisplay().
    };

    const originalUpdateSelectedDisplay = select.updateSelectedDisplay.bind(select);

    select.updateSelectedDisplay = async function (...args) {
        if (! this.isMultiple) {
            return originalUpdateSelectedDisplay(...args);
        }

        this.selectedDisplayVersion = this.selectedDisplayVersion + 1;
        const renderVersion = this.selectedDisplayVersion;

        if (! Array.isArray(this.state) || this.state.length === 0) {
            if (renderVersion !== this.selectedDisplayVersion) {
                return;
            }

            this.selectedDisplay?.replaceChildren();

            const placeholderSpan = document.createElement('span');
            placeholderSpan.textContent = this.placeholder;
            placeholderSpan.classList.add('fi-select-input-placeholder');
            this.selectedDisplay?.appendChild(placeholderSpan);
            renderSelectedTags(this, []);
            hideInitialTriggerSsr(this);

            if (this.isOpen) {
                this.deferPositionDropdown?.();
            }

            return;
        }

        await renderMultipleTriggerDisplay(this);

        if (renderVersion !== this.selectedDisplayVersion) {
            return;
        }

        if (this.isOpen) {
            this.deferPositionDropdown?.();
        }
    };

    const decorateSelectedOptionCheck = (optionEl) => {
        if (! optionEl?.classList.contains('fi-selected') || ! selectedOptionCheckIconHtml) {
            return;
        }

        const labelSpan = optionEl.querySelector(':scope > span');

        if (! labelSpan || labelSpan.classList.contains('fff-select-option-selected-row')) {
            return;
        }

        const labelContent = select.isHtmlAllowed ? labelSpan.innerHTML : labelSpan.textContent;

        labelSpan.innerHTML = '';
        labelSpan.classList.add('fff-select-option-selected-row');

        const labelWrapper = document.createElement('span');
        labelWrapper.className = 'fff-select-option-selected-row__label';

        if (select.isHtmlAllowed) {
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
    };

    const originalRenderOptions = select.renderOptions.bind(select);

    select.renderOptions = function () {
        if (! this.isMultiple) {
            originalRenderOptions();

            return;
        }

        originalRenderOptions();
        syncUserSelectSelectedStates(this);

        this.getVisibleOptions().forEach((optionEl) => {
            if (optionEl.classList.contains('fi-selected')) {
                decorateSelectedOptionCheck(optionEl);
            }
        });
    };

    select.hasAvailableOptions = function () {
        return this.options?.length > 0;
    };

    select.__fffDestroyUserSelectMultiple = () => {
        if (select.__fffUserSelectResizeListener) {
            window.removeEventListener('resize', select.__fffUserSelectResizeListener);
            select.__fffUserSelectResizeListener = null;
        }

        if (select.__fffUserSelectNamesResizeObserver) {
            select.__fffUserSelectNamesResizeObserver.disconnect();
            select.__fffUserSelectNamesResizeObserver = null;
        }

        if (select.__fffUserSelectNamesReflowFrame) {
            cancelAnimationFrame(select.__fffUserSelectNamesReflowFrame);
            select.__fffUserSelectNamesReflowFrame = null;
        }
    };

    renderMultipleTriggerDisplay(select);
    observeTriggerNamesContainer(select);

    return select.__fffDestroyUserSelectMultiple;
};
