import { defineConfig, devices } from '@playwright/test'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

import { DEFAULT_PLAYGROUND_BASE_URL, PLAYGROUND_AUTH_FILE, resolvePlaygroundBaseUrl } from './tests/e2e/global-setup.mjs'

const packageRoot = path.dirname(fileURLToPath(import.meta.url))

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 60_000,
    retries: process.env.CI ? 1 : 0,
    globalSetup: './tests/e2e/global-setup.mjs',
    projects: [
        {
            name: 'coordinator-fixture',
            testMatch: /field-smoke|year-scroll|icon-picker-field\.spec\.mjs|overlay-matrix\.spec\.mjs/,
            use: {
                ...devices['Desktop Chrome'],
                baseURL: 'http://127.0.0.1:3456',
            },
        },
        {
            name: 'playground',
            testMatch: /playground-.*\.spec\.mjs/,
            use: {
                ...devices['Desktop Chrome'],
                baseURL: resolvePlaygroundBaseUrl(),
                storageState: PLAYGROUND_AUTH_FILE,
            },
        },
    ],
    webServer: {
        command: `npx --yes serve "${packageRoot}" -p 3456`,
        url: 'http://127.0.0.1:3456/tests/e2e/fixtures/field-smoke.html',
        reuseExistingServer: ! process.env.CI,
        timeout: 120_000,
    },
})

export { DEFAULT_PLAYGROUND_BASE_URL }
