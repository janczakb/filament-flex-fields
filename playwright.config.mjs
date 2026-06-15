import { defineConfig, devices } from '@playwright/test'

const baseURL = process.env.FLEX_FIELDS_PLAYGROUND_URL ?? 'http://127.0.0.1:8000/admin/flex-fields-playground'

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 60_000,
    retries: process.env.CI ? 1 : 0,
    use: {
        baseURL,
        trace: 'on-first-retry',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
})
