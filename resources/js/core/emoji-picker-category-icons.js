import bulbIcon from '@gravity-ui/icons/svgs/bulb.svg'
import carIcon from '@gravity-ui/icons/svgs/car.svg'
import faceSmileIcon from '@gravity-ui/icons/svgs/face-smile.svg'
import flagIcon from '@gravity-ui/icons/svgs/flag.svg'
import magnifierIcon from '@gravity-ui/icons/svgs/magnifier.svg'
import mugIcon from '@gravity-ui/icons/svgs/mug.svg'
import personsIcon from '@gravity-ui/icons/svgs/persons.svg'
import planetEarthIcon from '@gravity-ui/icons/svgs/planet-earth.svg'
import sparklesIcon from '@gravity-ui/icons/svgs/sparkles.svg'
import squareHashtagIcon from '@gravity-ui/icons/svgs/square-hashtag.svg'
import targetIcon from '@gravity-ui/icons/svgs/target.svg'

const CATEGORY_GROUP_IDS = ['-1', '0', '1', '3', '4', '5', '6', '7', '8', '9']

const CATEGORY_ICON_SVGS = {
    '-1': sparklesIcon,
    0: faceSmileIcon,
    1: personsIcon,
    3: planetEarthIcon,
    4: mugIcon,
    5: carIcon,
    6: targetIcon,
    7: bulbIcon,
    8: squareHashtagIcon,
    9: flagIcon,
}

export function getCategoryIconGroupIds() {
    return [...CATEGORY_GROUP_IDS]
}

export function getCategoryIconSvg(groupId) {
    return CATEGORY_ICON_SVGS[String(groupId)] ?? null
}

export function prepareGravitySvg(svg) {
    return svg
        .replace(/<svg\b/, '<svg class="fff-gravity-icon" aria-hidden="true" focusable="false"')
        .replace(/\swidth="16"/, '')
        .replace(/\sheight="16"/, '')
}

export function injectCategoryIcons(picker) {
    const root = picker?.shadowRoot

    if (! root) {
        return
    }

    for (const button of root.querySelectorAll('.nav-button[data-group-id]')) {
        const groupId = button.getAttribute('data-group-id')
        const svgTemplate = getCategoryIconSvg(groupId)
        const navEmoji = button.querySelector('.nav-emoji')

        if (! navEmoji || ! svgTemplate) {
            continue
        }

        if (navEmoji.querySelector('.fff-gravity-icon')) {
            continue
        }

        navEmoji.replaceChildren()
        navEmoji.insertAdjacentHTML('beforeend', prepareGravitySvg(svgTemplate))
    }
}

export function injectSearchIcon(picker) {
    const root = picker?.shadowRoot
    const wrapper = root?.querySelector('.search-wrapper')

    if (! wrapper || wrapper.querySelector('.fff-search-icon')) {
        return
    }

    const icon = document.createElement('span')
    icon.className = 'fff-search-icon'
    icon.setAttribute('aria-hidden', 'true')
    icon.innerHTML = prepareGravitySvg(magnifierIcon)
    wrapper.prepend(icon)
}

export function injectPickerChrome(picker) {
    injectCategoryIcons(picker)
    injectSearchIcon(picker)
}

export function buildCategoryIconStyles(isDark, primaryColor) {
    const inactiveIconColor = isDark ? 'rgb(161 161 170)' : 'rgb(113 113 122)'
    const hoverIconColor = isDark ? 'rgb(228 228 231)' : 'rgb(63 63 70)'
    const searchIconColor = isDark ? 'rgb(113 113 122)' : 'rgb(161 161 170)'
    const surfaceBg = isDark ? 'rgb(39 39 42)' : 'rgb(255 255 255)'
    const borderColor = isDark ? 'rgb(63 63 70)' : 'rgb(228 228 231)'
    const shadowColor = isDark ? 'rgb(0 0 0 / 0.35)' : 'rgb(0 0 0 / 0.12)'

    return `
        .picker {
            display: flex !important;
            flex-direction: column !important;
        }

        .nav {
            order: 1;
            padding: 0.5rem 0.5rem 0 !important;
        }

        .indicator-wrapper {
            order: 2;
            flex-shrink: 0;
        }

        .search-row {
            order: 3;
            position: relative !important;
            z-index: 6 !important;
            isolation: isolate !important;
            padding: 0.625rem 0.75rem 0.75rem !important;
            border-bottom: none !important;
            overflow: visible !important;
        }

        .message {
            order: 4;
        }

        .tabpanel {
            order: 5;
        }

        .nav-button {
            border-radius: 0.5rem !important;
            padding: 0.25rem !important;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .nav-button:focus:not(:focus-visible) {
            outline: none !important;
            box-shadow: none !important;
        }

        .nav-emoji.emoji:hover,
        .nav-emoji.emoji:active,
        .nav-emoji.emoji.active {
            background: transparent !important;
        }

        .nav-emoji {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 1.75rem !important;
            height: 1.75rem !important;
            font-size: 0 !important;
            line-height: 0 !important;
            color: ${inactiveIconColor} !important;
            overflow: visible !important;
            transition: color 0.15s ease;
        }

        .nav-emoji .fff-gravity-icon {
            width: 1.125rem !important;
            height: 1.125rem !important;
            display: block !important;
        }

        .nav-button:hover .nav-emoji,
        .nav-button:focus-visible .nav-emoji {
            color: ${hoverIconColor} !important;
        }

        .nav-button[aria-selected="true"] .nav-emoji {
            color: ${primaryColor} !important;
        }

        .indicator {
            height: 2px !important;
            border-radius: 999px !important;
        }

        input.search {
            padding-left: 2.25rem !important;
        }

        .search-wrapper {
            position: relative !important;
        }

        .fff-search-icon {
            position: absolute !important;
            top: 50% !important;
            left: 0.75rem !important;
            width: 1rem !important;
            height: 1rem !important;
            transform: translateY(-50%) !important;
            color: ${searchIconColor} !important;
            pointer-events: none !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .fff-search-icon .fff-gravity-icon {
            width: 1rem !important;
            height: 1rem !important;
            display: block !important;
        }

        .skintone-button-wrapper {
            position: relative !important;
            flex-shrink: 0;
            z-index: 8 !important;
            background: ${surfaceBg} !important;
            border-radius: 999px !important;
        }

        .skintone-button-wrapper.expanded {
            z-index: 9 !important;
        }

        .skintone-button-wrapper #skintone-button.emoji {
            width: var(--total-emoji-size) !important;
            height: var(--total-emoji-size) !important;
            font-size: var(--emoji-size) !important;
        }

        .skintone-list {
            top: 8px !important;
            bottom: auto !important;
            inset-inline-end: 0.75rem !important;
            transform-origin: top center !important;
            z-index: 10 !important;
            overflow: hidden !important;
            background: ${surfaceBg} !important;
            border: 1px solid ${borderColor} !important;
            border-radius: 1.2rem !important;
            box-shadow: 0 10px 24px ${shadowColor} !important;
            transition: transform 0.16s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .skintone-list .emoji {
            width: var(--total-emoji-size) !important;
            height: var(--total-emoji-size) !important;
            font-size: var(--emoji-size) !important;
        }

        .tabpanel {
            position: relative !important;
            z-index: 1 !important;
        }

        .category {
            font-weight: 600 !important;
            font-size: 0.8125rem !important;
            letter-spacing: 0.01em;
            padding-top: 0.625rem !important;
            padding-bottom: 0.25rem !important;
        }
    `
}
