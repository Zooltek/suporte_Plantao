import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: 1,
    reporter: 'list',
    timeout: 30_000,
    expect: {
        timeout: 10_000,
    },
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8090',
        browserName: 'chromium',
        channel: 'chrome',
        ...devices['Desktop Chrome'],
        headless: true,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'off',
    },
});
