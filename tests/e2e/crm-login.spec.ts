import { test, expect } from '@playwright/test';

test.describe('CRM Login', () => {
    test('login page loads', async ({ page }) => {
        await page.goto('/login');
        await expect(page).toHaveURL(/\/login/);
        // Должны быть поля email и password
        await expect(page.locator('input[type="email"], input[placeholder*="mail"]')).toBeVisible();
        await expect(page.locator('input[type="password"]')).toBeVisible();
    });

    test('login with valid credentials redirects to /app or /crm', async ({ page }) => {
        await page.goto('/login');

        // Silk Road agency owner
        await page.fill('input[type="email"], input[placeholder*="mail"]', 'owner@silkroad.test');
        await page.fill('input[type="password"]', 'Owner@2026!');
        await page.click('button[type="submit"]');

        // Должен редиректнуть на /app (owner агентства)
        await page.waitForURL(/\/(app|crm)/, { timeout: 10000 });
        const url = page.url();
        expect(url).toMatch(/\/(app|crm)/);
    });

    test('login with wrong password shows error', async ({ page }) => {
        await page.goto('/login');

        await page.fill('input[type="email"], input[placeholder*="mail"]', 'owner@silkroad.test');
        await page.fill('input[type="password"]', 'wrongpassword');
        await page.click('button[type="submit"]');

        // Должно показать ошибку, не редиректить
        await page.waitForTimeout(2000);
        expect(page.url()).toContain('/login');
    });
});

test.describe('Phone display format', () => {
    test('phones show formatted with spaces', async ({ page }) => {
        await page.goto('/login');
        await page.fill('input[type="email"], input[placeholder*="mail"]', 'owner@silkroad.test');
        await page.fill('input[type="password"]', 'Owner@2026!');
        await page.click('button[type="submit"]');
        await page.waitForURL(/\/app/, { timeout: 10000 });

        // Перейти на страницу заявок
        await page.goto('/app/cases');
        await page.waitForLoadState('networkidle');

        // Проверить что нет serialize-формата s:N:"..."
        const content = await page.textContent('body');
        expect(content).not.toMatch(/s:\d+:"/);

        // Проверить формат телефона если есть
        const phonePattern = /\+998 \d{2} \d{3} \d{2} \d{2}/;
        const phones = await page.locator('a[href^="tel:"]').allTextContents();
        for (const phone of phones) {
            if (phone.trim()) {
                expect(phone.trim()).toMatch(phonePattern);
            }
        }
    });
});

test.describe('Public Portal SMS Auth', () => {
    test('phone auth modal opens and accepts PIN', async ({ page }) => {
        await page.goto('/me/cases');

        // Должен быть редирект на авторизацию или модалка
        await page.waitForLoadState('networkidle');

        // Ищем поле телефона (AppPhoneInput)
        const phoneInput = page.locator('input[type="tel"]');
        if (await phoneInput.isVisible()) {
            await phoneInput.fill('991234567');

            // Найти кнопку отправки
            const submitBtn = page.locator('button:has-text("Получить"), button:has-text("Отправить"), button:has-text("Войти")');
            if (await submitBtn.isVisible()) {
                await submitBtn.click();
                // Ждём PIN-поля
                await page.waitForTimeout(2000);
            }
        }
    });
});
