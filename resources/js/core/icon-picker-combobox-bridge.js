import { createComboboxEngine, DEFAULT_VIRTUAL_WINDOW_SIZE } from './combobox-engine.js'
import { ICON_PICKER_VIRTUAL_SCROLL_THRESHOLD } from './icon-picker-virtual-window.js'

function iconItemToOption(item) {
    const normalized = typeof item === 'string'
        ? { name: item, label: item }
        : {
            name: item?.name ?? '',
            label: item?.label ?? item?.name ?? '',
        }

    return {
        ...normalized,
        value: normalized.name,
        label: normalized.label,
    }
}

/**
 * Headless combobox engine bridge for IconPickerField (same runtime as SelectField).
 *
 * Server-side search stays in icon-picker-field; the engine owns highlight/selection/virtual window metadata.
 */
export function createIconPickerComboboxBridge(component, {
    itemsKey = 'loadedIconItems',
    virtualizeThreshold = ICON_PICKER_VIRTUAL_SCROLL_THRESHOLD,
    virtualWindowSize = DEFAULT_VIRTUAL_WINDOW_SIZE,
} = {}) {
    const resolveItems = () => {
        const value = component[itemsKey]

        return typeof value === 'function' ? value.call(component) : (value ?? [])
    }

    component._iconEngine = createComboboxEngine({
        options: resolveItems().map(iconItemToOption),
        searchable: false,
        getOptionValue: (option) => option.name ?? option.value,
        getOptionLabel: (option) => option.label ?? option.name,
        virtualizeThreshold,
        virtualWindowSize,
        initialSelectedValues: component.state ? [component.state] : [],
        onChange: (values) => {
            const next = values[0]

            if (next != null && typeof component.selectIcon === 'function') {
                component.selectIcon(next)
            }
        },
    })

    component._overlayEngine = component._iconEngine
    component.iconListRowHeight = 44
    component.virtualScrollTick = 0

    component.syncIconComboboxOptions = function syncIconComboboxOptions() {
        const items = resolveItems.call(this).map(iconItemToOption)

        this._iconEngine?.setOptions(items)

        const snapshot = this._iconEngine?.getSnapshot?.()

        if (snapshot && typeof this.activeIconIndex === 'number') {
            this.activeIconIndex = Math.max(snapshot.highlightedIndex, items.length > 0 ? 0 : -1)
        }
    }

    component.iconComboboxMoveHighlight = function iconComboboxMoveHighlight(delta) {
        this._iconEngine?.moveHighlight(delta)
        const snapshot = this._iconEngine?.getSnapshot?.()

        if (snapshot) {
            this.activeIconIndex = snapshot.highlightedIndex
        }

        this.scrollActiveIconIntoView?.()
    }

    component.iconComboboxSelectHighlighted = function iconComboboxSelectHighlighted() {
        if (this._iconEngine?.selectHighlighted?.()) {
            const snapshot = this._iconEngine.getSnapshot()

            this.activeIconIndex = snapshot.highlightedIndex
        }
    }

    component.iconComboboxFilteredWindow = function iconComboboxFilteredWindow() {
        return this._iconEngine?.filteredOptions?.() ?? {
            options: resolveItems.call(this),
            meta: { startIndex: 0, endIndex: resolveItems.call(this).length, total: resolveItems.call(this).length },
        }
    }

    component.$watch?.(itemsKey, () => {
        component.syncIconComboboxOptions?.()
    })

    component.syncIconComboboxOptions()
}
