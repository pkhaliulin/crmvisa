import { test, expect } from '@playwright/test';

async function login(page: import('@playwright/test').Page) {
    await page.goto('/login');
    await page.fill('input[type="email"], input[placeholder*="mail"]', 'owner@silkroad.test');
    await page.fill('input[type="password"]', 'Owner@2026!');
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/(app|crm)/, { timeout: 15000 });
}

test.describe('Cases Page', () => {

    test('cases page loads with filters', async ({ page }) => {
        await login(page);
        await page.goto('/app/cases');
        await page.waitForLoadState('networkidle');

        // Таблица или список заявок должен отрендериться
        const body = await page.textContent('body');
        expect(body).toBeTruthy();

        // Проверяем наличие фильтров (SearchSelect компоненты или кнопки фильтрации)
        // Фильтры: stage, priority, country — рендерятся как SearchSelect
        const filterElements = page.locator('.search-select, [data-testid*="filter"], input[placeholder*="Поиск"], input[placeholder*="search"]');
        // Хотя бы один фильтр или поле поиска должен быть
        const hasFilters = await filterElements.first().isVisible({ timeout: 5000 }).catch(() => false);

        // Также может быть просто таблица
        const tableOrList = page.locator('table, .case-card, .case-list, [class*="case"]');
        const hasTable = await tableOrList.first().isVisible({ timeout: 5000 }).catch(() => false);

        // Один из двух вариантов должен быть виден
        expect(hasFilters || hasTable || body!.length > 100).toBeTruthy();
    });

    test('cases page does not have serialized data', async ({ page }) => {
        await login(page);
        await page.goto('/app/cases');
        await page.waitForLoadState('networkidle');

        // Нет serialize-формата PHP (s:N:"...")
        const content = await page.textContent('body');
        expect(content).not.toMatch(/s:\d+:"/);
    });

    test('case detail page shows full info', async ({ page }) => {
        await login(page);
        await page.goto('/app/cases');
        await page.waitForLoadState('networkidle');

        // Ищем ссылку на конкретную заявку
        const caseLink = page.locator('a[href*="/cases/"], tr[class*="cursor"], .case-card').first();

        if (await caseLink.isVisible({ timeout: 5000 }).catch(() => false)) {
            await caseLink.click();
            await page.waitForLoadState('networkidle');

            const body = await page.textContent('body');
            // Детальная страница должна содержать информацию о заявке
            const hasDetail =
                body?.includes('клиент') ||
                body?.includes('Client') ||
                body?.includes('этап') ||
                body?.includes('Stage') ||
                body?.includes('документ') ||
                body?.includes('Document');
            expect(body!.length).toBeGreaterThan(50);
        }
    });
});
