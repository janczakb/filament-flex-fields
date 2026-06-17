import assert from 'node:assert/strict'
import test from 'node:test'

function installDomStub() {
    const htmlClassList = new Set()
    const bodyClassList = new Set()

    globalThis.window = globalThis
    globalThis.document = {
        documentElement: {
            classList: {
                contains: (name) => htmlClassList.has(name),
                add: (name) => htmlClassList.add(name),
                remove: (name) => htmlClassList.delete(name),
            },
        },
        body: {
            classList: {
                contains: (name) => bodyClassList.has(name),
            },
        },
        querySelector: () => null,
    }

    return {
        reset() {
            htmlClassList.clear()
            bodyClassList.clear()
        },
    }
}

test('resolveIsDark returns true when documentElement has dark class', async () => {
    const dom = installDomStub()
    const { resolveIsDark } = await import('../../resources/js/core/theme-utils.js')

    document.documentElement.classList.add('dark')

    assert.equal(resolveIsDark(), true)
    dom.reset()
})

test('resolveTeleportedMenuZIndex uses modal layer when a Filament modal is open', async () => {
    const dom = installDomStub()
    const { resolveTeleportedMenuZIndex } = await import('../../resources/js/core/theme-utils.js')

    document.querySelector = () => ({ classList: { contains: () => true } })

    assert.equal(resolveTeleportedMenuZIndex(), 'var(--fff-z-dropdown-modal, 60)')
    dom.reset()
})

test('resolveTeleportedMenuZIndex uses default dropdown layer without an open modal', async () => {
    const dom = installDomStub()
    const { resolveTeleportedMenuZIndex } = await import('../../resources/js/core/theme-utils.js')

    document.querySelector = () => null

    assert.equal(resolveTeleportedMenuZIndex(), 'var(--fff-z-dropdown, 20)')
    dom.reset()
})
