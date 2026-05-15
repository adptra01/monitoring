import { test, expect } from '@playwright/test';

test.describe('License Management', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@monitor.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
  });

  test('TC-07: Admin bisa melihat daftar lisensi', async ({ page }) => {
    await page.goto('/licenses');
    await expect(page.locator('text=Licenses')).toBeVisible();
  });

  test('TC-08: Admin bisa membuat lisensi baru', async ({ page }) => {
    await page.goto('/licenses/create');

    // Select first product
    await page.selectOption('[wire\\:model*="product_id"]', { index: 1 });
    // Select first user
    await page.selectOption('[wire\\:model*="user_id"]', { index: 1 });
    // Set max devices
    await page.fill('[wire\\:model*="max_devices"]', '5');

    await page.click('button[type="submit"]');
    
    await page.waitForURL('/licenses');
    await expect(page.locator('text=License created successfully')).toBeVisible();
  });

  test('TC-09: Search lisensi berfungsi', async ({ page }) => {
    await page.goto('/licenses');
    const searchInput = page.locator('input[type="search"]');
    await searchInput.fill('NON-EXISTENT-KEY');
    
    // Wait for Livewire to debounce/refresh
    await page.waitForTimeout(1000);
    
    const rows = page.locator('tbody tr');
    // Depending on UI, might show "No results" or empty table
    const count = await rows.count();
    expect(count).toBeLessThanOrEqual(1); // Usually 0 or 1 with "No results" row
  });
});
