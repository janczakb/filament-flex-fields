import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

import { chromium } from '@playwright/test'

const packageRoot = path.dirname(path.dirname(path.dirname(fileURLToPath(import.meta.url))))
export const PLAYGROUND_AUTH_FILE = path.join(packageRoot, '.auth', 'playground.json')

export const DEFAULT_PLAYGROUND_BASE_URL = 'https://wyachts-super-app.test/admin/flex-fields-playground'

/**
 * @returns {string}
 */
export function resolvePlaygroundBaseUrl() {
    const fromEnv = (process.env.FLEX_FIELDS_PLAYGROUND_URL ?? '').trim()

    if (fromEnv !== '') {
        return fromEnv.replace(/\/?$/, '')
    }

    return DEFAULT_PLAYGROUND_BASE_URL
}

/**
 * @param {string} slug
 * @returns {string}
 */
export function playgroundUrl(slug) {
    const base = `${resolvePlaygroundBaseUrl()}/`

    return `${base}${slug.replace(/^\//, '')}`
}

/**
 * @param {import('@playwright/test').Page} page
 */
export async function loginToPlaygroundIfNeeded(page) {
    if (! page.url().includes('/login')) {
        return
    }

    const email = process.env.FLEX_FIELDS_PLAYGROUND_EMAIL ?? 'admin@wyachts.com'
    const password = process.env.FLEX_FIELDS_PLAYGROUND_PASSWORD ?? 'password'

    await page.getByLabel(/email/i).fill(email)
    await page.locator('#password, input[name="password"], input[type="password"]').first().fill(password)
    await page.getByRole('button', { name: /sign in|log in/i }).click()
    await page.waitForURL(/\/admin(?!\/login)/, { timeout: 30_000 })
}

/**
 * @param {import('@playwright/test').Page} page
 * @param {string} slug
 */
export async function gotoPlaygroundPage(page, slug) {
    const url = playgroundUrl(slug)

    await page.goto(url, { waitUntil: 'domcontentloaded' })
    await loginToPlaygroundIfNeeded(page)

    if (! page.url().includes('flex-fields-playground')) {
        await page.goto(url, { waitUntil: 'domcontentloaded' })
    }

    await page.waitForURL(/flex-fields-playground\//, { timeout: 30_000 })
    await page.locator('.fi-main, .fi-body, main, .fff-flex-file-upload, .fff-signature-field, .fff-voice-recorder').first().waitFor({
        state: 'visible',
        timeout: 30_000,
    })
}

/**
 * @param {import('@playwright/test').Page} page
 */
export async function ensurePlaygroundAuthenticated(page) {
    if (page.url().includes('/login')) {
        await loginToPlaygroundIfNeeded(page)
    }

    await page.waitForURL(/flex-fields-playground\//, { timeout: 30_000 })
}

/**
 * Playwright global setup — persist authenticated Filament session for playground specs.
 */
export default async function globalSetup() {
    fs.mkdirSync(path.dirname(PLAYGROUND_AUTH_FILE), { recursive: true })

    const browser = await chromium.launch()
    const context = await browser.newContext()
    const page = await context.newPage()

    try {
        await gotoPlaygroundPage(page, 'file-upload')
        await context.storageState({ path: PLAYGROUND_AUTH_FILE })
    } finally {
        await browser.close()
    }
}
