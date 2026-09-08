/**
 * Shared overlay virtualization thresholds.
 * All window math goes through fff-virtual-adapter → @tanstack/virtual-core.
 * Select keeps 100 for playground copy parity ("≥ 100").
 * Siblings (phone/country/currency) historically used 50; IconPicker list uses 80.
 */
export const OVERLAY_VIRTUALIZE_THRESHOLD = 100

export const SELECT_VIRTUALIZE_THRESHOLD = OVERLAY_VIRTUALIZE_THRESHOLD

export const SIBLING_VIRTUALIZE_THRESHOLD = 50

export const ICON_VIRTUALIZE_THRESHOLD = 80
