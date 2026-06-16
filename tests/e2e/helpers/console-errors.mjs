export function trackConsoleErrors(page) {
    const errors = []

    page.on('pageerror', (error) => {
        errors.push(error.message)
    })

    page.on('console', (message) => {
        if (message.type() === 'error') {
            errors.push(message.text())
        }
    })

    return {
        errors,
        assertClean() {
            if (errors.length > 0) {
                throw new Error(`Unexpected console errors:\n${errors.join('\n')}`)
            }
        },
    }
}

export async function waitForSelectCoordinatorAttached(page, selector = '.fff-select-field__shell') {
    await page.locator(`${selector}[data-fff-select-attached="true"]`).first().waitFor({
        state: 'attached',
        timeout: 10_000,
    })
}
