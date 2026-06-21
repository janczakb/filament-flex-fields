import { defineConfig, devices } from '@playwright/test'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const packageRoot = path.dirname(fileURLToPath(import.meta.url))
const playgroundBaseURL = process.env.FLEX_FIELDS_PLAYGROUND_URL

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 60_000,
    retries: process.env.CI ? 1 : 0,
    projects: [
        {
            name: 'coordinator-fixture',
            testMatch: /field-smoke\.spec\.mjs/,
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
                baseURL: playgroundBaseURL ?? 'http://127.0.0.1:8000/admin/flex-fields-playground',
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
