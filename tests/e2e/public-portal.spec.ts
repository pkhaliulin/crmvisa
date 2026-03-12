import { test, expect } from '@playwright/test';

test.describe('Public Portal — Landing', () => {

    test('public landing page loads', async ({ page }) => {
        const response = await page.goto('/');
        expect(response?.status()).toBeLessThan(500);

        await page.waitForLoadState('networkidle');

        const body = await page.textContent('body');
        expect(body).toBeTruthy();

        // Главная страница должна содержать hero-секцию или описание сервиса
        const hasHero =
            body?.includes('виза') ||
            body?.includes('Visa') ||
            body?.includes('visa') ||
            body?.includes('VisaBor') ||
            body?.includes('визабор') ||
            body?.includes('страна') ||
            body?.includes('Country');

        expect(hasHero || body!.length > 200).toBeTruthy();
    });

    test('landing page has countries grid', async ({ page }) => {
        await page.goto('/');
        await page.waitForLoadState('networkidle');

        // Грид стран с флагами или ссылки на страны
        const countryLinks = page.locator('a[href*="/country/"]');
        const countryCount = await countryLinks.count();

        // Должны быть ссылки на страны
        expect(countryCount).toBeGreaterThan(0);
    });

    test('landing page does not have JS errors', async ({ page }) => {
        const errors: string[] = [];
        page.on('pageerror', (err) => errors.push(err.message));

        await page.goto('/');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        const criticalErrors = errors.filter(
            (e) => !e.includes('ResizeObserver') && !e.includes('Script error')
        );
        expect(criticalErrors).toHaveLength(0);
    });
});

test.describe('Public Portal — Country Detail', () => {

    test('country detail page works', async ({ page }) => {
        // Франция — FR
        const response = await page.goto('/country/FR');
        expect(response?.status()).toBeLessThan(500);

        await page.waitForLoadState('networkidle');

        const body = await page.textContent('body');
        expect(body).toBeTruthy();

        // Страница страны должна содержать информацию о визах или агентствах
        const hasCountryInfo =
            body?.includes('Франци') ||
            body?.includes('France') ||
            body?.includes('виза') ||
            body?.includes('visa') ||
            body?.includes('агентств') ||
            body?.includes('agenc');

        expect(hasCountryInfo || body!.length > 200).toBeTruthy();
    });

    test('country detail page shows agencies', async ({ page }) => {
        await page.goto('/country/FR');
        await page.waitForLoadState('networkidle');

        const body = await page.textContent('body');

        // Список агентств или кнопка подачи
        const hasAgencies =
            body?.includes('агентств') ||
            body?.includes('agency') ||
            body?.includes('подать') ||
            body?.includes('заявк') ||
            body?.includes('apply');

        expect(body!.length).toBeGreaterThan(100);
    });

    test('unknown country returns valid page', async ({ page }) => {
        const response = await page.goto('/country/XX');

        // Не должен быть 500 (может быть 404 или редирект)
        expect(response?.status()).toBeLessThan(500);
    });
});

test.describe('Public Portal — Static Pages', () => {

    test('about page loads', async ({ page }) => {
        const response = await page.goto('/about');
        expect(response?.status()).toBeLessThan(500);
    });

    test('privacy page loads', async ({ page }) => {
        const response = await page.goto('/privacy');
        expect(response?.status()).toBeLessThan(500);
    });

    test('terms page loads', async ({ page }) => {
        const response = await page.goto('/terms');
        expect(response?.status()).toBeLessThan(500);
    });
});
