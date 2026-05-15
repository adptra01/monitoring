import { test, expect } from '@playwright/test';

test.describe('Product Management', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@monitor.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
  });

  test('TC-04: Admin bisa melihat daftar produk', async ({ page }) => {
    await page.goto('/products');
    await expect(page.locator('table, [role="table"]')).toBeVisible();
    await expect(page.locator('text=Products')).toBeVisible();
  });

  test('TC-05: Admin bisa membuat produk baru', async ({ page }) => {
    await page.goto('/products/create');
    
    const productName = `Test Product ${Date.now()}`;
    const productSlug = `test-product-${Date.now()}`;

    // Using wire:model selectors since it's Livewire
    await page.fill('[wire\\:model*="name"]', productName);
    await page.fill('[wire\\:model*="slug"]', productSlug);
    await page.fill('[wire\\:model*="description"]', 'Description for e2e product');
    
    await page.click('button[type="submit"]');

    await page.waitForURL('/products');
    await expect(page.locator(`text=${productName}`)).toBeVisible();
  });

  test('TC-06: Validasi form produk - nama wajib diisi', async ({ page }) => {
    await page.goto('/products/create');
    await page.click('button[type="submit"]');
    // Check for validation message
    await expect(page.locator('text=required')).toBeVisible();
  });
});
