/**
 * Resolve a playground slug against FLEX_FIELDS_PLAYGROUND_URL.
 *
 * @param {string} slug
 */
export function playgroundUrl(slug) {
    const base = (process.env.FLEX_FIELDS_PLAYGROUND_URL ?? '').replace(/\/?$/, '/')

    return `${base}${slug.replace(/^\//, '')}`
}

/**
 * @param {import('@playwright/test').Page} page
 */
export async function ensurePlaygroundAuthenticated(page) {
    if (page.url().includes('/login')) {
        await page.getByLabel(/email/i).fill(process.env.FLEX_FIELDS_PLAYGROUND_EMAIL ?? 'admin@wyachts.com')
        await page.locator('#password').fill(process.env.FLEX_FIELDS_PLAYGROUND_PASSWORD ?? 'password')
        await page.getByRole('button', { name: /sign in|log in/i }).click()
    }

    await page.waitForURL(/flex-fields-playground\//, { timeout: 15_000 })
}
