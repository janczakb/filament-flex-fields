@php
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;
    use Filament\Support\Enums\IconSize;

    $verifiedIconHtml = \Filament\Support\generate_icon_html(GravityIcon::SealCheck, size: IconSize::ExtraSmall)?->toHtml() ?? '';
    $userSelectNoOptionsIconHtml = \Filament\Support\generate_icon_html(GravityIcon::Persons, size: IconSize::Large)?->toHtml() ?? '';
    $userSelectNoResultsIconHtml = \Filament\Support\generate_icon_html(GravityIcon::Magnifier, size: IconSize::Large)?->toHtml() ?? '';
@endphp

const verifiedIconHtml = @js($verifiedIconHtml);
const userSelectNoOptionsIconHtml = @js($userSelectNoOptionsIconHtml);
const userSelectNoResultsIconHtml = @js($userSelectNoResultsIconHtml);
const FFF_USER_SELECT_VIRTUAL_THRESHOLD = 30;
const FFF_USER_SELECT_ROW_HEIGHT = 52;
const FFF_USER_SELECT_OVERSCAN = 5;

const escapeHtml = (value) => {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

const renderUserAvatarHtml = (user, layout = 'list') => {
    if (! user) {
        return '';
    }

    const sizeClass = {
        trigger: 'fff-user-select__avatar--trigger',
        tag: 'fff-user-select__avatar--tag',
        list: 'fff-user-select__avatar--list',
    }[layout] ?? 'fff-user-select__avatar--list';

    const avatarUrl = user.avatarUrl ?? user.image ?? null;
    const initials = user.initials ?? '';
    const verified = Boolean(user.verified);

    let html = `<span class="fff-user-select__avatar ${sizeClass}" aria-hidden="true">`;
    html += '<span class="fff-user-select__avatar-surface">';

    if (avatarUrl) {
        html += `<img src="${escapeHtml(avatarUrl)}" alt="" class="fff-user-select__avatar-image" loading="lazy" />`;
    } else {
        html += `<span class="fff-user-select__avatar-initials">${escapeHtml(initials)}</span>`;
    }

    html += '</span>';

    if (verified && verifiedIconHtml) {
        html += `<span class="fff-user-select__verified-badge" title="Verified" aria-hidden="true">${verifiedIconHtml}</span>`;
    }

    html += '</span>';

    return html;
};

const renderUserOptionHtml = (user, layout = 'list') => {
    if (! user) {
        return '';
    }

    const name = user.name ?? '';
    const email = user.email ?? user.description ?? null;
    const layoutClass = {
        trigger: 'fff-user-select-option--trigger',
        tag: 'fff-user-select-option--tag',
        list: 'fff-user-select-option--list',
    }[layout] ?? 'fff-user-select-option--list';

    let html = `<span class="fff-user-select-option ${layoutClass}">`;
    html += renderUserAvatarHtml(user, layout);

    if (layout === 'tag') {
        html += `<span class="fff-user-select-option__name">${escapeHtml(name)}</span>`;
    } else {
        html += '<span class="fff-user-select-option__content">';
        html += `<span class="fff-user-select-option__name">${escapeHtml(name)}</span>`;

        if (email && layout !== 'tag') {
            html += `<span class="fff-user-select-option__email">${escapeHtml(email)}</span>`;
        }

        html += '</span>';
    }

    html += '</span>';

    return html;
};

const renderUserTagHtml = (value, user, removable = true) => {
    const name = user?.name ?? String(value ?? '');

    let html = `<span class="fff-user-select__selected-tag fff-tags-field__tag" data-value="${escapeHtml(value)}">`;
    html += `<span class="fff-user-select__selected-tag-content">${renderUserOptionHtml(user, 'tag')}</span>`;

    if (removable) {
        html += `<button type="button" class="fff-tags-field__tag-remove fff-user-select__selected-tag-remove" aria-label="Remove ${escapeHtml(name)}" tabindex="-1">${tagRemoveIconHtml}</button>`;
    }

    html += '</span>';

    return html;
};

const ensureUserRepository = (select) => {
    if (! select.userRepository) {
        select.userRepository = {};
    }

    return select.userRepository;
};

const storeUserInRepository = (select, value, user) => {
    if (value === undefined || ! user) {
        return;
    }

    const repository = ensureUserRepository(select);
    repository[value] = user;
    repository[String(value)] = user;
};

const resolveInitialUserEntry = (selectInstance, value) => {
    const entries = selectInstance.initialSelectedUserEntries;

    if (! Array.isArray(entries)) {
        return null;
    }

    const key = String(value);

    return entries.find((entry) => String(entry?.value) === key) ?? null;
};

const findUserInOptions = (select, value) => {
    const key = String(value);
    const repository = select.userRepository ?? {};

    if (repository[key]) {
        return repository[key];
    }

    const walk = (options) => {
        if (! Array.isArray(options)) {
            return null;
        }

        for (const option of options) {
            if (option.options && Array.isArray(option.options)) {
                const found = walk(option.options);

                if (found) {
                    return found;
                }

                continue;
            }

            if (String(option.value) === key && option.user) {
                return option.user;
            }
        }

        return null;
    };

    return walk(select.options);
};

const collectFlatUserOptions = (options) => {
    const flat = [];

    const walk = (items) => {
        if (! Array.isArray(items)) {
            return;
        }

        for (const option of items) {
            if (option.options && Array.isArray(option.options)) {
                walk(option.options);

                continue;
            }

            if (option.value !== undefined) {
                flat.push(option);
            }
        }
    };

    walk(options);

    return flat;
};

const optionsHaveGroups = (options) => {
    return Array.isArray(options) && options.some((option) => Array.isArray(option.options));
};

const userSelectStatesEqual = (a, b) => {
    const normalize = (values) => {
        if (! Array.isArray(values)) {
            return [];
        }

        return values.map((value) => String(value)).sort();
    };

    const normalizedA = normalize(a);
    const normalizedB = normalize(b);

    if (normalizedA.length !== normalizedB.length) {
        return false;
    }

    return normalizedA.every((value, index) => value === normalizedB[index]);
};

const isUserSelectValueSelected = (select, value) => {
    if (! select?.isMultiple || ! Array.isArray(select.state) || select.state.length === 0) {
        return false;
    }

    const key = String(value);

    return select.state.some((stateValue) => String(stateValue) === key);
};

const filterUserSelectUnselectedOptions = (select, options) => {
    if (! Array.isArray(options)) {
        return [];
    }

    const filterItems = (items) => {
        const filtered = [];

        for (const option of items) {
            if (option.options && Array.isArray(option.options)) {
                const childOptions = filterItems(option.options);

                if (childOptions.length > 0) {
                    filtered.push({
                        ...option,
                        options: childOptions,
                    });
                }

                continue;
            }

            if (option.value !== undefined && ! isUserSelectValueSelected(select, option.value)) {
                filtered.push(option);
            }
        }

        return filtered;
    };

    return filterItems(options);
};

const syncUserSelectSelectedStates = (select) => {
    if (! select?.isMultiple) {
        return;
    }

    const selectedValues = new Set(
        Array.isArray(select.state) ? select.state.map((value) => String(value)) : [],
    );

    select.getVisibleOptions().forEach((optionEl) => {
        const value = optionEl.getAttribute('data-value');
        const isSelected = value !== null && selectedValues.has(String(value));

        optionEl.classList.toggle('fi-selected', isSelected);
        optionEl.setAttribute('aria-selected', isSelected ? 'true' : 'false');
    });
};

const patchUserSelectVirtualScroll = (select) => {
    const originalRenderOptions = select.renderOptions.bind(select);

    select.renderVirtualUserOptions = function () {
        const flatOptions = collectFlatUserOptions(this.options).filter((option) => {
            return ! isUserSelectValueSelected(this, option.value);
        });

        this.__fffVirtualFlatOptions = flatOptions;
        this.optionsList.innerHTML = '';
        this.optionsList.className = 'fi-dropdown-list fff-user-select-virtual-list';

        if (flatOptions.length === 0) {
            if (this.isSearching) {
                if (this.optionsList.parentNode === this.dropdown) {
                    this.dropdown.removeChild(this.optionsList);
                }

                return;
            }

            if (this.searchQuery) {
                this.showNoResultsMessage();
            } else if (this.hasInitialNoOptionsMessage || this.hasDynamicOptions) {
                this.showNoOptionsMessage();
            }

            if (this.optionsList.parentNode === this.dropdown) {
                this.dropdown.removeChild(this.optionsList);
            }

            return;
        }

        this.hideLoadingState();

        if (this.optionsList.parentNode !== this.dropdown) {
            this.dropdown.appendChild(this.optionsList);
        }

        const totalHeight = flatOptions.length * FFF_USER_SELECT_ROW_HEIGHT;
        const spacerTop = document.createElement('div');
        const spacerBottom = document.createElement('div');
        const windowList = document.createElement('ul');
        windowList.className = 'fi-dropdown-list fff-user-select-virtual-window';

        spacerTop.className = 'fff-user-select-virtual-spacer-top';
        spacerBottom.className = 'fff-user-select-virtual-spacer-bottom';
        spacerTop.style.height = '0px';
        spacerBottom.style.height = '0px';

        this.optionsList.replaceChildren(spacerTop, windowList, spacerBottom);
        this.__fffVirtualWindowList = windowList;
        this.__fffVirtualSpacerTop = spacerTop;
        this.__fffVirtualSpacerBottom = spacerBottom;
        this.__fffVirtualTotalHeight = totalHeight;

        const renderWindow = () => {
            const scrollTop = this.optionsList.scrollTop;
            const viewportHeight = this.optionsList.clientHeight || 320;
            const startIndex = Math.max(0, Math.floor(scrollTop / FFF_USER_SELECT_ROW_HEIGHT) - FFF_USER_SELECT_OVERSCAN);
            const visibleCount = Math.ceil(viewportHeight / FFF_USER_SELECT_ROW_HEIGHT) + (FFF_USER_SELECT_OVERSCAN * 2);
            const endIndex = Math.min(flatOptions.length, startIndex + visibleCount);

            spacerTop.style.height = `${startIndex * FFF_USER_SELECT_ROW_HEIGHT}px`;
            spacerBottom.style.height = `${Math.max(0, (flatOptions.length - endIndex) * FFF_USER_SELECT_ROW_HEIGHT)}px`;

            windowList.replaceChildren();

            for (let index = startIndex; index < endIndex; index++) {
                const option = flatOptions[index];
                windowList.appendChild(this.createOptionElement(option.value, option));
            }

            syncUserSelectSelectedStates(this);
        };

        if (this.__fffVirtualScrollListener) {
            this.optionsList.removeEventListener('scroll', this.__fffVirtualScrollListener);
        }

        this.__fffVirtualScrollListener = () => renderWindow();
        this.optionsList.addEventListener('scroll', this.__fffVirtualScrollListener, { passive: true });

        renderWindow();
    };

    select.renderOptions = function (...args) {
        const flatOptions = collectFlatUserOptions(this.options);

        if (flatOptions.length > FFF_USER_SELECT_VIRTUAL_THRESHOLD && ! optionsHaveGroups(this.options)) {
            this.renderVirtualUserOptions();

            return;
        }

        if (this.__fffVirtualScrollListener && this.optionsList) {
            this.optionsList.removeEventListener('scroll', this.__fffVirtualScrollListener);
            this.__fffVirtualScrollListener = null;
        }

        let restoredOptions = null;

        if (this.isMultiple && Array.isArray(this.state) && this.state.length > 0) {
            restoredOptions = this.options;
            this.options = filterUserSelectUnselectedOptions(this, this.options);
        }

        originalRenderOptions(...args);

        if (restoredOptions !== null) {
            this.options = restoredOptions;
        }

        syncUserSelectSelectedStates(this);
    };
};

const FFF_USER_SELECT_SKELETON_ROWS = 3;

const removeUserSelectDropdownMessages = (dropdown) => {
    dropdown
        ?.querySelectorAll('[data-fff-user-select-loading], [data-fff-user-select-empty], .fi-select-input-message')
        .forEach((element) => element.remove());
};

const buildUserSelectEmptyState = (select, type) => {
    const isSearch = type === 'search';
    const empty = document.createElement('div');
    empty.className = 'fff-user-select__dropdown-empty';
    empty.dataset.fffUserSelectEmpty = type;
    empty.setAttribute('role', 'status');

    const icon = document.createElement('span');
    icon.className = 'fff-user-select__dropdown-empty-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.innerHTML = isSearch ? userSelectNoResultsIconHtml : userSelectNoOptionsIconHtml;

    const title = document.createElement('span');
    title.className = 'fff-user-select__dropdown-empty-title';
    title.textContent = isSearch
        ? (select.noSearchResultsMessage ?? 'No results found')
        : (select.noOptionsMessage ?? 'No options available');

    const hint = document.createElement('span');
    hint.className = 'fff-user-select__dropdown-empty-hint';

    if (isSearch) {
        hint.textContent = 'Try a different name or email address.';
    } else if (select.isSearchable && Number(select.__fffMinSearchLength ?? 0) > 0) {
        hint.textContent = `Type at least ${select.__fffMinSearchLength} characters to search for users.`;
    } else {
        hint.textContent = 'No users are available right now.';
    }

    empty.appendChild(icon);
    empty.appendChild(title);
    empty.appendChild(hint);

    return empty;
};

const buildUserSelectLoadingSkeleton = (select, isSearching = false) => {
    const skeleton = document.createElement('div');
    skeleton.className = 'fff-user-select__dropdown-skeleton';
    skeleton.dataset.fffUserSelectLoading = 'true';
    skeleton.setAttribute('role', 'status');
    skeleton.setAttribute('aria-live', 'polite');
    skeleton.setAttribute('aria-busy', 'true');
    skeleton.setAttribute(
        'aria-label',
        isSearching ? (select.searchingMessage ?? 'Searching users') : (select.loadingMessage ?? 'Loading users'),
    );

    const rowCount = Math.min(
        Math.max(1, Number(select.optionsLimit ?? FFF_USER_SELECT_SKELETON_ROWS)),
        FFF_USER_SELECT_SKELETON_ROWS,
    );

    for (let index = 0; index < rowCount; index++) {
        const option = document.createElement('div');
        option.className = 'fff-user-select__dropdown-skeleton-option';
        option.style.setProperty('--fff-user-select-skeleton-i', String(index));

        const avatar = document.createElement('span');
        avatar.className = 'fff-user-select__dropdown-skeleton-avatar';
        avatar.setAttribute('aria-hidden', 'true');

        const body = document.createElement('span');
        body.className = 'fff-user-select__dropdown-skeleton-body';

        const primary = document.createElement('span');
        primary.className = 'fff-user-select__dropdown-skeleton-line is-primary';

        const secondary = document.createElement('span');
        secondary.className = 'fff-user-select__dropdown-skeleton-line is-secondary';

        body.appendChild(primary);
        body.appendChild(secondary);
        option.appendChild(avatar);
        option.appendChild(body);
        skeleton.appendChild(option);
    }

    return skeleton;
};

const patchUserSelectLoadingSkeleton = (select) => {
    if (! select || select.__fffUserSelectLoadingPatched) {
        return;
    }

    if (! select.hasDynamicSearchResults && ! select.hasDynamicOptions) {
        return;
    }

    select.__fffUserSelectLoadingPatched = true;

    const originalShowLoadingState = select.showLoadingState.bind(select);
    const originalHideLoadingState = select.hideLoadingState.bind(select);

    select.showLoadingState = function (isSearching = false) {
        if (this.optionsList?.parentNode === this.dropdown) {
            this.dropdown.removeChild(this.optionsList);
        }

        removeUserSelectDropdownMessages(this.dropdown);
        originalHideLoadingState();

        const skeleton = buildUserSelectLoadingSkeleton(this, isSearching);
        this.dropdown.appendChild(skeleton);

        if (this.isOpen) {
            this.deferPositionDropdown?.();
        }
    };

    select.hideLoadingState = function () {
        removeUserSelectDropdownMessages(this.dropdown);

        originalHideLoadingState();
    };
};

const patchUserSelectEmptyStates = (select) => {
    if (! select || select.__fffUserSelectEmptyPatched) {
        return;
    }

    select.__fffUserSelectEmptyPatched = true;

    const showEmptyState = function (type) {
        if (this.optionsList?.parentNode === this.dropdown) {
            this.dropdown.removeChild(this.optionsList);
        }

        this.hideLoadingState();
        removeUserSelectDropdownMessages(this.dropdown);
        this.dropdown.appendChild(buildUserSelectEmptyState(this, type));

        if (this.isOpen) {
            this.deferPositionDropdown?.();
        }
    };

    select.showNoOptionsMessage = function () {
        if (this.isSearching && this.dropdown?.querySelector('[data-fff-user-select-loading]')) {
            return;
        }

        showEmptyState.call(this, 'options');
    };

    select.showNoResultsMessage = function () {
        if (this.isSearching && this.dropdown?.querySelector('[data-fff-user-select-loading]')) {
            return;
        }

        showEmptyState.call(this, 'search');
    };
};

const patchUserSelectSearch = (select) => {
    if (! select || select.__fffUserSelectSearchPatched) {
        return;
    }

    select.__fffUserSelectSearchPatched = true;
    select.__fffSearchResultsCache = new Map();

    const originalHandleSearch = select.handleSearch.bind(select);

    select.handleSearch = function (event) {
        const query = event.target.value.trim();
        const minSearchLength = Number(this.__fffMinSearchLength ?? 0);

        if (
            query !== ''
            && minSearchLength > 0
            && query.length < minSearchLength
            && this.hasDynamicSearchResults
            && this.getSearchResultsUsing
        ) {
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = null;
            }

            this.searchQuery = query;
            this.options = [];
            this.hideLoadingState();
            this.renderOptions();
            this.showNoResultsMessage();

            return;
        }

        if (
            query !== ''
            && this.hasDynamicSearchResults
            && query.length >= minSearchLength
        ) {
            removeUserSelectDropdownMessages(this.dropdown);
        }

        if (
            query !== ''
            && this.hasDynamicSearchResults
            && this.getSearchResultsUsing
            && this.__fffSearchResultsCache.has(query)
        ) {
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = null;
            }

            const cached = this.__fffSearchResultsCache.get(query);

            this.searchQuery = query;
            this.options = cached;
            this.populateLabelRepositoryFromOptions(cached);
            this.hideLoadingState();
            this.renderOptions();

            if (this.options.length === 0) {
                this.showNoResultsMessage();
            }

            return;
        }

        return originalHandleSearch(event);
    };

    const originalGetSearchResultsUsing = select.getSearchResultsUsing;

    if (typeof originalGetSearchResultsUsing === 'function') {
        select.getSearchResultsUsing = async function (search) {
            const results = await originalGetSearchResultsUsing(search);
            const normalizedResults = Array.isArray(results)
                ? results
                : results && Array.isArray(results.options)
                    ? results.options
                    : [];

            const trimmedSearch = String(search ?? '').trim();

            if (trimmedSearch !== '') {
                this.__fffSearchResultsCache.set(trimmedSearch, normalizedResults);
            }

            return results;
        };
    }
};

const patchUserSelectClient = (select) => {
    if (! select || select.__fffUserSelectClientPatched) {
        return;
    }

    select.__fffUserSelectClientPatched = true;
    ensureUserRepository(select);

    if (Array.isArray(select.initialSelectedUserEntries)) {
        for (const entry of select.initialSelectedUserEntries) {
            storeUserInRepository(select, entry?.value, entry?.user);
        }
    }

    const originalPopulateLabelRepositoryFromOptions = select.populateLabelRepositoryFromOptions.bind(select);

    select.populateLabelRepositoryFromOptions = function (options) {
        originalPopulateLabelRepositoryFromOptions(options);

        const walk = (items) => {
            if (! Array.isArray(items)) {
                return;
            }

            for (const option of items) {
                if (option.options && Array.isArray(option.options)) {
                    walk(option.options);

                    continue;
                }

                if (option.value !== undefined && option.user) {
                    storeUserInRepository(this, option.value, option.user);
                }
            }
        };

        walk(options);
    };

    const originalCreateOptionElement = select.createOptionElement.bind(select);

    select.createOptionElement = function (value, label) {
        if (
            typeof label === 'object'
            && label !== null
            && label.fffClientRender
            && label.user
        ) {
            let optionValue = label.value ?? value;
            let isDisabled = label.isDisabled || false;
            const user = label.user;

            const option = document.createElement('li');
            option.className = 'fi-dropdown-list-item fi-select-input-option';

            if (isDisabled) {
                option.classList.add('fi-disabled');
            }

            const optionId = `fi-select-input-option-${Math.random().toString(36).substring(2, 11)}`;
            option.id = optionId;
            option.setAttribute('role', 'option');
            option.setAttribute('data-value', optionValue);
            option.setAttribute('tabindex', '0');
            option.setAttribute('aria-label', user.name ?? String(optionValue));

            if (isDisabled) {
                option.setAttribute('aria-disabled', 'true');
            }

            const isSelected = this.isMultiple
                ? isUserSelectValueSelected(this, optionValue)
                : this.state === optionValue;

            option.setAttribute('aria-selected', isSelected ? 'true' : 'false');

            if (isSelected) {
                option.classList.add('fi-selected');
            }

            const labelSpan = document.createElement('span');

            if (this.isHtmlAllowed) {
                labelSpan.innerHTML = renderUserOptionHtml(user, 'list');
            } else {
                labelSpan.textContent = user.name ?? String(optionValue);
            }

            option.appendChild(labelSpan);
            storeUserInRepository(this, optionValue, user);

            if (! isDisabled) {
                option.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    this.selectOption(optionValue);

                    if (this.isMultiple) {
                        if (this.isSearchable && this.searchInput) {
                            setTimeout(() => this.searchInput?.focus(), 0);
                        } else {
                            this.dropdown?.focus();
                        }
                    }
                });
            }

            return option;
        }

        return originalCreateOptionElement(value, label);
    };

    const originalAddSingleSelectionDisplay = select.addSingleSelectionDisplay.bind(select);

    select.addSingleSelectionDisplay = function (selectedLabel, target = this.selectedDisplay) {
        const initialEntry = resolveInitialUserEntry(this, this.state);
        const user = initialEntry?.user ?? findUserInOptions(this, this.state);

        if (user) {
            const labelContainer = document.createElement('span');
            labelContainer.className = 'fi-select-input-value-label';
            labelContainer.innerHTML = renderUserOptionHtml(user, 'trigger');
            target.appendChild(labelContainer);
            storeUserInRepository(this, this.state, user);
            hideInitialTriggerSsr(this);

            if (! this.canSelectPlaceholder) {
                return;
            }

            if (this.container.querySelector('.fi-select-input-value-remove-btn')) {
                return;
            }

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'fi-select-input-value-remove-btn';
            removeButton.innerHTML = clearIconHtml;
            removeButton.setAttribute('aria-label', 'Clear selection');

            removeButton.addEventListener('click', (event) => {
                event.stopPropagation();
                this.selectOption('');
            });

            removeButton.addEventListener('keydown', (event) => {
                if (event.key === ' ' || event.key === 'Enter') {
                    event.preventDefault();
                    event.stopPropagation();
                    this.selectOption('');
                }
            });

            this.container.appendChild(removeButton);
            this.container.classList.add('fi-select-input-ctn-clearable');

            return;
        }

        originalAddSingleSelectionDisplay(selectedLabel, target);
    };

    const originalOnStateChange = select.onStateChange?.bind(select);

    select.onStateChange = function (state) {
        if (this.isMultiple && Array.isArray(state)) {
            this.__fffLocalState = [...state];
            this.__fffLocalStateVersion = (this.__fffLocalStateVersion ?? 0) + 1;
        }

        if (typeof originalOnStateChange === 'function') {
            return originalOnStateChange(state);
        }
    };

    const originalOpenDropdown = select.openDropdown.bind(select);

    select.openDropdown = async function (...args) {
        const stateSnapshot = this.isMultiple && Array.isArray(this.state)
            ? [...this.state]
            : null;
        const hadPendingLocalChange = stateSnapshot !== null
            && this.__fffLocalState !== undefined
            && ! userSelectStatesEqual(this.__fffLocalState, stateSnapshot);

        await originalOpenDropdown(...args);

        if (
            hadPendingLocalChange
            && stateSnapshot !== null
            && Array.isArray(this.state)
            && (
                this.state.length !== stateSnapshot.length
                || ! userSelectStatesEqual(this.state, stateSnapshot)
            )
        ) {
            this.state = [...stateSnapshot];
            this.__fffLocalState = [...stateSnapshot];
            this.__fffLocalStateVersion = (this.__fffLocalStateVersion ?? 0) + 1;
        }
    };

    patchUserSelectVirtualScroll(select);
    patchUserSelectLoadingSkeleton(select);
    patchUserSelectEmptyStates(select);
    patchUserSelectSearch(select);
    select.populateLabelRepositoryFromOptions(select.options);
};
