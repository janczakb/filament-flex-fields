import { createResourceScope } from '../../runtime/lifecycle/resource-scope.js'
import { createImperativeMenuSurface } from '../../overlay/menu/imperative-surface.js'

/** Free text suggestions share Select geometry, exclusivity, portals and transitions. */
export function createInputSuggestions(owner, { createSurface = createImperativeMenuSurface } = {}) {
    const scope = createResourceScope()
    const input = owner.$refs.input
    const shell = input.closest('.fff-flex-text-input__shell') ?? input
    const panel = document.createElement('div')
    const list = document.createElement('div')
    panel.className = 'fi-dropdown-panel fff-select-dropdown-panel fff-select-dropdown-panel--md fff-select-headless-menu fff-teleported-menu fff-input-suggestions'
    panel.dataset.descriptionPosition = owner.options.suggestionDescriptionPosition ?? 'below'
    panel.hidden = true
    list.className = 'fi-select-input-options-ctn fff-input-suggestions__list'
    list.id = `${input.id}-fff-suggestions`
    list.setAttribute('role', 'listbox'); panel.append(list)
    let surface, timer, generation = 0, selected = -1, items = [], busy = false
    let inFlight = false, pending = false
    const cache = new Map()
    // SSR already carries combobox attributes. Restore the caller's attributes,
    // not that enhanced snapshot, when the optional capability is disabled.
    const originalAttributes = new Map(['role', 'aria-autocomplete', 'aria-controls', 'aria-expanded', 'aria-activedescendant'].map(name => [name,
        owner.options.nativeSuggestionAttributes?.[name] ?? null]))
    const cancelPending = () => { generation++; pending = false; busy = false; clearTimeout(timer); list.setAttribute('aria-busy', 'false') }
    const close = () => { cancelPending(); surface?.closeTeleportedMenu(); owner.suggestionsOpen = false; owner.suggestionActiveDescendant = null; input.removeAttribute('aria-activedescendant') }
    const render = (records, message = null) => {
        items = records; selected = -1; owner.suggestionActiveDescendant = null; list.replaceChildren()
        records.forEach((item, index) => {
            const option = document.createElement('button')
            option.type = 'button'; option.disabled = Boolean(item.disabled)
            option.className = 'fi-select-input-option'; option.classList.toggle('fi-disabled', Boolean(item.disabled)); option.id = `${list.id}-${index}`
            option.setAttribute('role', 'option'); option.setAttribute('aria-selected', 'false')
            option.setAttribute('aria-disabled', String(item.disabled)); option.tabIndex = -1
            const row = document.createElement('span')
            const content = document.createElement('span'); content.className = 'fff-input-suggestions__content'
            const label = document.createElement('span'); label.className = 'fff-select-headless-option-label'; label.textContent = item.label; content.append(label)
            if (item.description) { const description = document.createElement('small'); description.className = 'fff-input-suggestions__description'; description.textContent = item.description; content.append(description) }
            row.append(content); option.append(row)
            option.addEventListener('pointerdown', event => event.preventDefault())
            option.addEventListener('click', () => select(index)); list.append(option)
        })
        if (!records.length) {
            const state = document.createElement('div'); state.className = 'fff-input-suggestions__state'
            state.setAttribute('role', 'status')
            state.dataset.kind = message === owner.options.messages.suggestions_error ? 'error' : message ? 'loading' : 'empty'
            if (!message) {
                const title = document.createElement('span'); title.className = 'fff-input-suggestions__empty-title'; title.textContent = owner.options.messages.suggestions_empty
                const hint = document.createElement('span'); hint.className = 'fff-input-suggestions__empty-hint'; hint.textContent = owner.options.messages.suggestions_empty_hint
                state.append(title, hint)
            } else state.textContent = message
            list.append(state)
            if (message === owner.options.messages.suggestions_error) {
                const retry = document.createElement('button'); retry.type = 'button'; retry.className = 'fff-input-suggestions__retry'; retry.textContent = owner.options.messages.retry
                retry.addEventListener('pointerdown', event => event.preventDefault()); retry.addEventListener('click', search); list.append(retry)
            }
        }
        if (records.length) {
            const spacer = document.createElement('div'); spacer.className = 'fff-select-dropdown-list-end-spacer'
            spacer.setAttribute('aria-hidden', 'true'); list.append(spacer)
        }
        list.setAttribute('aria-busy', String(busy))
        surface?.updateMenuPosition({ markReady: surface.menuReady })
    }
    const select = index => {
        if (!scope.disposed && owner.options.editable && owner.options.suggestionsEnabled && items[index] && !items[index].disabled) {
            owner.commitToolValue(items[index].value)
            clearTimeout(timer); close(); input.focus({ preventScroll: true })
        }
    }
    const open = () => {
        if (!surface) surface = createSurface({ anchor:shell, ownershipAnchor:input, controllerOwner:owner.$el, panel,
            width:0, matchTriggerWidth:true,
            position: owner.options.suggestionPlacement, align: 'start',
            onClose: () => { cancelPending(); panel.hidden = true; owner.suggestionsOpen = false; owner.suggestionActiveDescendant = null; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant') } })
        panel.hidden = false; surface.menuOpen = true; owner.suggestionsOpen = true
        input.setAttribute('aria-expanded', 'true')
    }
    const search = async () => {
        if (scope.disposed || !owner.options.editable || !owner.options.suggestionsEnabled || document.activeElement !== input || owner.composing) return
        const query = input.value
        if (Array.from(query).length < owner.options.suggestionMinLength) { close(); return }
        if (inFlight) { pending = true; return }
        const current = ++generation
        const valid = () => !scope.disposed && current === generation && input.value === query && document.activeElement === input && owner.options.suggestionsEnabled
        busy = false
        open()
        if (!owner.options.asyncSuggestions) {
            const normalized = query.toLocaleLowerCase()
            render(owner.options.suggestions.filter(item => `${item.label} ${item.value} ${item.description}`.toLocaleLowerCase().includes(normalized)).slice(0, owner.options.suggestionLimit)); return
        }
        if (cache.has(query)) { render(cache.get(query)); return }
        busy = true; inFlight = true; render([], owner.options.messages.suggestions_loading)
        try {
            const records = await owner.$wire.callSchemaComponentMethod(owner.options.componentKey, 'getTextInputSuggestionsForJs', { search: query })
            if (!valid()) return
            if (!Array.isArray(records)) throw new Error('Invalid suggestions')
            if (cache.size >= 20) cache.delete(cache.keys().next().value)
            cache.set(query, records); busy = false; render(records)
        } catch {
            if (valid()) { busy = false; render([], owner.options.messages.suggestions_error) }
        } finally {
            inFlight = false; busy = false; list.setAttribute('aria-busy', 'false')
            if (pending) { pending = false; if (!scope.disposed) search() }
        }
    }
    input.setAttribute('role', 'combobox'); input.setAttribute('aria-autocomplete', 'list')
    input.setAttribute('aria-controls', list.id); input.setAttribute('aria-expanded', 'false')
    scope.listen(input, 'keydown', event => {
        if (event.key === 'Escape' && owner.suggestionsOpen) { event.preventDefault(); close() }
        if (event.key === 'Enter' && owner.suggestionsOpen && selected >= 0) { event.preventDefault(); select(selected) }
        if (['ArrowDown', 'ArrowUp'].includes(event.key) && owner.suggestionsOpen) {
            event.preventDefault(); const direction = event.key === 'ArrowDown' ? 1 : -1
            if (selected < 0) selected = direction === 1 ? -1 : 0
            for (let count = 0; count < items.length; count++) {
                selected = (selected + direction + items.length) % items.length
                if (!items[selected].disabled) break
            }
            if (selected >= 0 && items[selected] && !items[selected].disabled) {
                Array.from(list.children).forEach((el, index) => { el.setAttribute('aria-selected', String(index === selected)); el.classList.toggle('fi-highlighted', index === selected) })
                owner.suggestionActiveDescendant = `${list.id}-${selected}`
                input.setAttribute('aria-activedescendant', `${list.id}-${selected}`)
                list.children[selected]?.scrollIntoView({ block: 'nearest' })
            }
        }
    })
    scope.listen(panel, 'pointerdown', event => {
        if (event.target.closest('[data-fff-overlay-handle], .fff-overlay-sheet__handle')) {
            event.preventDefault()
        }
    })
    scope.listen(input, 'blur', () => { clearTimeout(timer); close() })
    scope.defer(() => {
        generation++; clearTimeout(timer); surface?.dispose(); panel.remove(); cache.clear()
        for (const [name, value] of originalAttributes) { if (value === null) input.removeAttribute(name); else input.setAttribute(name, value) }
    })
    return { schedule() { generation++; clearTimeout(timer); timer = setTimeout(search, owner.options.suggestionDebounce) }, dispose() { scope.dispose() } }
}
