export function resolveTriggerLabel(option) {
    if (option.triggerLabel !== undefined) {
        return option.triggerLabel;
    }

    return option.label;
}

export function findTriggerLabelInOptions(value, options) {
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
}

export function populateRepositoryWithTriggerLabels(select, options) {
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
}
