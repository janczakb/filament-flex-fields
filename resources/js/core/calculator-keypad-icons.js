import deleteIcon from '@gravity-ui/icons/svgs/delete.svg'
import equalIcon from '@gravity-ui/icons/svgs/equal.svg'
import minusIcon from '@gravity-ui/icons/svgs/minus.svg'
import percentIcon from '@gravity-ui/icons/svgs/percent.svg'
import plusIcon from '@gravity-ui/icons/svgs/plus.svg'
import xmarkIcon from '@gravity-ui/icons/svgs/xmark.svg'

const multiplyIcon = xmarkIcon

const divideIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 16 16"><circle cx="8" cy="4.25" r="1" fill="currentColor"/><path fill="currentColor" fill-rule="evenodd" d="M1.75 8a.75.75 0 0 1 .75-.75h11a.75.75 0 0 1 0 1.5h-11A.75.75 0 0 1 1.75 8" clip-rule="evenodd"/><circle cx="8" cy="11.75" r="1" fill="currentColor"/></svg>'

const plusMinusIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="none"><path fill="currentColor" d="M208.48535,64.48535l-144,144a12.0001,12.0001,0,0,1-16.9707-16.9707l144-144a12.0001,12.0001,0,0,1,16.9707,16.9707ZM60,112a12,12,0,0,0,24,0V84h28a12,12,0,0,0,0-24H84V32a12,12,0,0,0-24,0V60H32a12,12,0,0,0,0,24H60Zm164,60H144a12,12,0,0,0,0,24h80a12,12,0,0,0,0-24Z"/></svg>'

const CALCULATOR_KEY_ICONS = {
    delete: deleteIcon,
    xmark: xmarkIcon,
    percent: percentIcon,
    plus: plusIcon,
    minus: minusIcon,
    equal: equalIcon,
    multiply: multiplyIcon,
    divide: divideIcon,
    plusMinus: plusMinusIcon,
}

export function prepareCalculatorKeyIconSvg(svg) {
    return svg
        .replace(/<svg\b/, '<svg class="fff-gravity-icon fff-calculator-panel__gravity-icon" aria-hidden="true" focusable="false"')
        .replace(/\swidth="16"/, '')
        .replace(/\sheight="16"/, '')
}

export function getCalculatorKeyIconSvg(iconName) {
    const svg = CALCULATOR_KEY_ICONS[iconName]

    if (! svg) {
        return null
    }

    return prepareCalculatorKeyIconSvg(svg)
}
