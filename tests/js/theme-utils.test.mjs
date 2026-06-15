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

test('resolveIsDark returns false when light mode is explicit', async () => {
    const dom = installDomStub()
    const { resolveIsDark } = await import('../../resources/js/core/theme-utils.js')

    globalThis.matchMedia = () => ({ matches: false })

    assert.equal(resolveIsDark(), false)
    dom.reset()
})
