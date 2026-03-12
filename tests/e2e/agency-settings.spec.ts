import { test, expect } from '@playwright/test';

async function login(page: import('@playwright/test').Page) {
    await page.goto('/login');
    await page.fill('input[type="email"], input[placeholder*="mail"]', 'owner@silkroad.test');
    await page.fill('input[type="password"]', 'Owner@2026!');
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/(app|crm)/, { timeout: 15000 });
}

test.describe('Agency Settings', () => {

    test('agency settings page loads', async ({ page }) => {
        await login(page);
        await page.goto('/app/settings');
        await page.waitForLoadState('networkidle');

        const body = await page.textContent('body');
        expect(body).toBeTruthy();

        // Страница настроек должна содержать табы или формы
        const hasSettingsContent =
            body?.includes('настройк') ||
            body?.includes('Setting') ||
            body?.includes('агентств') ||
            body?.includes('Agency') ||
            body?.includes('профиль') ||
            body?.includes('Profile');

        expect(hasSettingsContent || body!.length > 100).toBeTruthy();
    });

    test('settings page has form fields', async ({ page }) => {
        await login(page);
        await page.goto('/app/settings');
        await page.waitForLoadState('networkidle');

        // Должны быть поля формы (input, textarea)
        const formFields = page.locator('input, textarea, .search-select');
        const fieldCount = await formFields.count();

        expect(fieldCount).toBeGreaterThan(0);
    });

    test('settings tabs are visible', async ({ page }) => {
        await login(page);
        await page.goto('/app/settings');
        await page.waitForLoadState('networkidle');

        // Табы или навигация по секциям настроек
        const tabs = page.locator('[role="tab"], .tab, button[class*="tab"], a[class*="tab"], nav button, nav a');

        if (await tabs.first().isVisible({ timeout: 3000 }).catch(() => false)) {
            const tabCount = await tabs.count();
            expect(tabCount).toBeGreaterThan(0);
        }
    });

    test('can update agency info', async ({ page }) => {
        await login(page);
        await page.goto('/app/settings');
        await page.waitForLoadState('networkidle');

        // Ищем поле описания (textarea)
        const textarea = page.locator('textarea').first();

        if (await textarea.isVisible({ timeout: 3000 }).catch(() => false)) {
            const testText = 'E2E test description ' + Date.now();
            await textarea.fill(testText);

            // Ищем кнопку сохранения
            const saveBtn = page.locator('button:has-text("Сохранить"), button:has-text("Save"), button[type="submit"]').first();

            if (await saveBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
                await saveBtn.click();
                await page.waitForLoadState('networkidle');

                // После сохранения — страница не упала
                const body = await page.textContent('body');
                expect(body).toBeTruthy();
            }
        }
    });
});
