import { expect, test } from '@playwright/test';

function trackBrowserIssues(page) {
    const consoleErrors = [];
    const pageErrors = [];
    const requestFailures = [];

    page.on('console', (message) => {
        if (message.type() === 'error') {
            consoleErrors.push(message.text());
        }
    });

    page.on('pageerror', (error) => {
        pageErrors.push(error.message);
    });

    page.on('requestfailed', (request) => {
        requestFailures.push({
            method: request.method(),
            url: request.url(),
            errorText: request.failure()?.errorText ?? 'unknown',
        });
    });

    return { consoleErrors, pageErrors, requestFailures };
}

async function assertNoBrokenAssetLoading(page, issues) {
    const headHtml = await page.locator('head').innerHTML();

    expect(headHtml).toContain('/build/');
    expect(headHtml).not.toContain('localhost:5173');

    const resourceUrls = await page.evaluate(() =>
        performance.getEntriesByType('resource').map((entry) => entry.name)
    );

    expect(resourceUrls.some((url) => url.includes('/build/'))).toBeTruthy();
    expect(resourceUrls.some((url) => url.includes('localhost:5173'))).toBeFalsy();

    const failedLocalhostRequests = issues.requestFailures.filter(({ url }) => url.includes('localhost:5173'));

    expect(failedLocalhostRequests).toEqual([]);
    expect(issues.pageErrors).toEqual([]);
    expect(issues.consoleErrors).toEqual([]);
}

test.describe('Acesso via IP', () => {
    test('login público carrega assets buildados e mantém JS funcional', async ({ page }) => {
        const issues = trackBrowserIssues(page);

        await page.goto('/login', { waitUntil: 'networkidle' });

        await expect(page.getByRole('heading', { name: 'Bem-vindo ao Suporte' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Acessar Conta' })).toBeVisible();
        await expect(page.locator('#theme-toggle')).toBeVisible();

        await assertNoBrokenAssetLoading(page, issues);

        await page.locator('#theme-toggle').click();

        await expect
            .poll(() => page.evaluate(() => localStorage.getItem('theme')))
            .toBe('ocean');

        await expect
            .poll(() => page.evaluate(() => document.documentElement.classList.contains('ocean')))
            .toBeTruthy();
    });

    test('login administrativo mantém a interface funcional via IP', async ({ page }) => {
        const issues = trackBrowserIssues(page);

        await page.goto('/admin/login', { waitUntil: 'networkidle' });

        await expect(page.getByRole('heading', { name: 'Acesso Restrito' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Acessar Conta' })).toBeVisible();
        await expect(page.locator('#theme-toggle')).toBeVisible();

        await assertNoBrokenAssetLoading(page, issues);
    });
});
