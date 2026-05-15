import { test, expect } from '@playwright/test';

test.describe('Admin Authentication', () => {

  test('TC-01: Login sukses dengan kredensial admin valid', async ({ page }) => {
    await page.goto('/login');
    
    await page.fill('input[name="email"]', 'admin@testing.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');

    await expect(page).toHaveURL('/dashboard', { timeout: 10000 });
    await expect(page.locator('text=Dashboard')).toBeVisible();
  });

  test('TC-02: Login gagal dengan password salah', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@testing.com');
    await page.fill('input[name="password"]', 'wrong-password');
    await page.click('button[type="submit"]');

    await expect(page).toHaveURL('/login');
    // Laravel usually returns errors in a list or specific div
    await expect(page.locator('text=credentials')).toBeVisible();
  });

  test('TC-03: Guest tidak bisa akses dashboard', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page).not.toHaveURL('/dashboard');
    await expect(page).toHaveURL(/\/login/);
  });
});
