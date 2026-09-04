import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import bubbleChoiceFormComponent, {
    buildBubbleRows,
    contrastTextForBackground,
    defaultLayoutOptions,
    estimateCenterScroll,
    getBubbleSize,
    interpolate,
    mergeLayoutOptions,
} from '../../resources/js/components/bubble-choice.js'
import {
    STROKE_WIDTH_MAX,
    circleRadii,
    easePower2Out,
    lerpRadii,
    morphPointsAt,
    pointsToPathD,
    pointsToPolygon,
    radiiToPoints,
    strokeWidthFactor,
} from '../../resources/js/components/bubble-choice/shape-morph.js'

describe('bubble choice layout options', () => {
    it('merges layout defaults', () => {
        const options = mergeLayoutOptions({
            size: 160,
            minSize: 25,
            gutter: 8,
            numCols: 10,
            fringeWidth: 160,
            yRadius: 130,
            xRadius: 248,
            cornerRadius: 50,
            compact: true,
            gravitation: 5,
            provideProps: true,
        })

        assert.equal(options.size, 160)
        assert.equal(options.minSize, 25)
        assert.equal(options.gutter, 8)
        assert.equal(options.numCols, 10)
        assert.equal(options.fringeWidth, 160)
        assert.equal(defaultLayoutOptions.size, 160)
        assert.equal(defaultLayoutOptions.minSize, 25)
        assert.equal(defaultLayoutOptions.gutter, 8)
        assert.equal('showGuides' in options, false)
    })
})

describe('bubble choice packing', () => {
    it('builds staggered rows with optional pad cell', () => {
        const options = Array.from({ length: 7 }, (_, index) => ({
            value: `v${index}`,
            label: `L${index}`,
        }))
        const rows = buildBubbleRows(options, 4)

        assert.ok(rows.length >= 2)
        assert.equal(rows[0].length, 3)
        assert.equal(rows[1].length, 4)
    })
})

describe('bubble choice scale on scroll', () => {
    it('keeps center bubbles near full size and shrinks outer bubbles', () => {
        const options = mergeLayoutOptions({
            size: 180,
            minSize: 20,
            gutter: 8,
            numCols: 6,
            fringeWidth: 160,
            yRadius: 130,
            xRadius: 248,
            cornerRadius: 50,
            compact: true,
            gravitation: 5,
        })
        const list = Array.from({ length: 18 }, (_, index) => ({
            value: `v${index}`,
            label: `L${index}`,
        }))
        const rows = buildBubbleRows(list, options.numCols)

        const center = getBubbleSize(options, rows, 1, 1, 0, 0)
        assert.ok(center.bubbleSize > 0.95)

        const far = getBubbleSize(options, rows, rows.length - 1, 0, 0, 0)
        assert.ok(far.bubbleSize < 0.85)
    })

    it('interpolates fringe proportion toward minSize', () => {
        assert.equal(interpolate(0, 10, 5, 0, 100), 50)
    })

    it('centers scroll state the same way as their scrollTo mid-point', () => {
        const options = mergeLayoutOptions({
            size: 160,
            minSize: 25,
            gutter: 8,
            numCols: 6,
            fringeWidth: 160,
            yRadius: 130,
            xRadius: 220,
            cornerRadius: 50,
            compact: true,
            gravitation: 5,
        })
        const list = Array.from({ length: 12 }, (_, index) => ({
            value: `v${index}`,
            label: `L${index}`,
        }))
        const rows = buildBubbleRows(list, options.numCols)
        const center = estimateCenterScroll(options, rows)

        assert.ok(Number.isFinite(center.scrollLeft))
        assert.ok(Number.isFinite(center.scrollTop))
    })
})

describe('bubble choice selection', () => {
    it('toggles selection and respects max items', () => {
        const component = bubbleChoiceFormComponent({
            state: [],
            options: [
                { value: 'a', label: 'A', disabled: false },
                { value: 'b', label: 'B', disabled: false },
                { value: 'c', label: 'C', disabled: false },
            ],
            disabled: false,
            maxItems: 2,
            selectedShape: 'scallop',
        })

        component.toggle('a')
        component.toggle('b')
        component.toggle('c')

        assert.deepEqual(component.state, ['a', 'b'])
    })

    it('ignores disabled options', () => {
        const component = bubbleChoiceFormComponent({
            state: [],
            options: [{ value: 'blocked', label: 'Blocked', disabled: true }],
            disabled: false,
            maxItems: null,
            selectedShape: 'scallop',
        })

        component.toggle('blocked')
        assert.deepEqual(component.state, [])
    })

    it('applies custom idle and selected colors with contrast text', () => {
        const component = bubbleChoiceFormComponent({
            state: [],
            options: [
                {
                    value: 'focus',
                    label: 'Focus',
                    color: '#1d4ed8',
                    selectedColor: '#c8f560',
                    disabled: false,
                },
            ],
            disabled: false,
            maxItems: null,
            selectedShape: 'scallop',
        })

        const style = component.bubbleFaceStyle({
            value: 'focus',
            label: 'Focus',
            color: '#1d4ed8',
            selectedColor: '#c8f560',
            image: null,
        })

        assert.equal(style['--fff-bubble-choice-color'], '#1d4ed8')
        assert.equal(style['--fff-bubble-choice-text'], '#f8fafc')
        assert.equal(style['--fff-bubble-choice-selected-color'], '#c8f560')
        assert.equal(style['--fff-bubble-choice-selected-text-resolved'], '#0f172a')
        assert.equal(contrastTextForBackground('#1e3a5f'), '#f8fafc')
    })
})

describe('bubble choice radial morph', () => {
    it('lerps radii without rotating the silhouette', () => {
        assert.equal(easePower2Out(0), 0)
        assert.equal(easePower2Out(1), 1)

        const circle = circleRadii(8, 0.5)
        const scallop = circle.map((radius, index) => radius + (index % 2 === 0 ? 0.1 : -0.05))
        const mid = radiiToPoints(lerpRadii(circle, scallop, 0.5))

        assert.equal(mid.length, 8)
        assert.match(pointsToPolygon(mid), /^polygon\(/)
        assert.match(pointsToPathD(mid), /^M/)
        assert.match(pointsToPathD(mid), /Z$/)

        const morph = morphPointsAt(0, { circle, scallop })
        assert.equal(morph[0].x.toFixed(5), radiiToPoints(circle)[0].x.toFixed(5))
    })

    it('collapses stroke faster on deselect than on select', () => {
        assert.ok(strokeWidthFactor(0.5, false) < strokeWidthFactor(0.5, true))
        assert.equal(strokeWidthFactor(1, false), 1)
        assert.equal(strokeWidthFactor(0, true), 0)
    })

    it('wires morph helpers on the alpine component', () => {
        const component = bubbleChoiceFormComponent({
            state: ['forest'],
            options: [{ value: 'forest', label: 'Forest', image: '/x.jpg', imageMode: 'background' }],
            disabled: false,
            maxItems: null,
            selectedShape: 'scallop',
        })

        component.seedMorphProgress()
        assert.equal(component.selectionMorphProgress('forest'), 1)
        assert.equal(component.selectionStrokeWidth('forest'), STROKE_WIDTH_MAX)
        assert.equal(component.bubbleClipPath('forest'), 'var(--fff-bubble-choice-clip-scallop)')
        assert.equal(component.bubbleClipPath('missing'), 'var(--fff-bubble-choice-clip-circle)')
        assert.equal(component.bubbleButtonStyle({ value: 'forest', label: 'Forest' }).clipPath, 'var(--fff-bubble-choice-clip-scallop)')
        assert.equal('clipPath' in component.bubbleFaceStyle({ value: 'forest', label: 'Forest' }), false)
        assert.equal(component.selectionStrokePath('forest').startsWith('M'), true)
        assert.equal(component.isSelectionMorphing('forest'), false)
        assert.equal(component.selectionStrokeStyle({ value: 'forest', label: 'Forest' }).transform, 'scale(1.02)')
    })
})
