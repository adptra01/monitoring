import { test, expect } from '@playwright/test';

test.describe('Full End-to-End CRUD Flow', () => {
  test.describe.configure({ mode: 'serial' });

  let page;
  const timestamp = Date.now();
  const testData = {
    productName: `E2E Product ${timestamp}`,
    productSlug: `e2e-product-${timestamp}`,
    productUpdatedName: `E2E Product Updated ${timestamp}`,
    planName: `E2E Plan ${timestamp}`,
    planSlug: `e2e-plan-${timestamp}`,
    licenseKey: '',
  };

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@testing.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
  });

  test.afterAll(async () => {
    await page.close();
  });

  test('Step 1: Product CRUD', async () => {
    await page.goto('/products/create');
    await page.getByLabel('Name').fill(testData.productName);
    // Slug might auto-fill via debounce, but we fill it to be safe
    await page.getByLabel('Slug').fill(testData.productSlug);
    await page.getByLabel('Description').fill('E2E Test Description');
    await page.click('button[type="submit"]');
    
    await page.waitForURL('/products');
    await expect(page.locator(`text=${testData.productName}`).first()).toBeVisible();

    await page.locator(`tr:has-text("${testData.productName}") >> a[href*="/edit"]`).first().click();
    
    await page.getByLabel('Name').fill(testData.productUpdatedName);
    await page.click('button[type="submit"]');
    
    await page.waitForURL('/products');
    await expect(page.locator(`text=${testData.productUpdatedName}`).first()).toBeVisible();
  });

  test('Step 2: Plan CRUD', async () => {
    await page.goto('/plans/create');
    
    await page.getByLabel('Product').selectOption({ label: testData.productUpdatedName });
    await page.getByLabel('Plan Name').fill(testData.planName);
    await page.getByLabel('Slug').fill(testData.planSlug);
    await page.getByLabel('Monthly Price (IDR)').fill('100000');
    await page.getByLabel('Max Devices').fill('5');
    await page.click('button[type="submit"]');
    
    await page.waitForURL('/plans');
    await expect(page.locator(`text=${testData.planName}`).first()).toBeVisible();
  });

  test('Step 3: License CRUD', async () => {
    await page.goto('/licenses/create');
    await page.getByLabel('Product').selectOption({ label: testData.productUpdatedName });
    await page.waitForTimeout(1000); // Wait for Livewire
    await page.getByLabel('Plan').selectOption({ label: testData.planName });
    
    await page.getByLabel('Assign to User').selectOption({ label: 'Super Admin' });
    await page.getByLabel('Max Devices').fill('5');
    await page.click('button[type="submit"]');
    
    await page.waitForURL('/licenses');
    await expect(page.locator('table')).toContainText(testData.planName);
  });

  test('Step 4: Cleanup (Delete)', async () => {
    await page.goto('/plans');
    await page.locator(`tr:has-text("${testData.planName}") >> button[wire\\:click*="confirmDelete"]`).first().click();
    await page.locator('button[type="submit"]:has-text("Delete")').click();
    await expect(page.locator(`text=${testData.planName}`)).toHaveCount(0);
  });
});
