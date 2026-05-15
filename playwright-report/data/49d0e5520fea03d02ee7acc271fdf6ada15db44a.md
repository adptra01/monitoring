# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: full-flow.spec.ts >> Full End-to-End CRUD Flow >> Step 1: Product CRUD
- Location: tests\e2e\full-flow.spec.ts:30:3

# Error details

```
Error: locator.fill: Error: strict mode violation: getByLabel('Password') resolved to 2 elements:
    1) <input type="password" name="password" label="Password" required="required" data-flux-control="" placeholder="Password" data-flux-group-target="" autocomplete="current-password" aria-labelledby="lofi-label-c533dd20a11618" class="w-full border rounded-lg block disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-10 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placehold…/> aka getByRole('textbox', { name: 'Password' })
    2) <button type="button" x-on:click="toggle()" x-data="fluxInputViewable" x-bind:data-viewable-open="open" data-flux-button="data-flux-button" aria-label="Toggle password visibility" class="relative items-center font-medium justify-center gap-2 whitespace-nowrap disabled:opacity-75 dark:disabled:opacity-75 disabled:cursor-default disabled:pointer-events-none justify-center h-8 text-sm rounded-md w-8 inline-flex -ms-1.5 -me-1.5 bg-transparent hover:bg-zinc-800/5 dark:hover:bg-white/15 text-zinc-500 hover:…>…</button> aka getByRole('button', { name: 'Toggle password visibility' })

Call log:
  - waiting for getByLabel('Password')

```

# Page snapshot

```yaml
- generic [ref=e1]:
  - generic [ref=e3]:
    - link "Laravel" [ref=e4] [cursor=pointer]:
      - /url: http://laravel_monitor.test
      - img [ref=e6]
      - generic [ref=e8]: Laravel
    - generic [ref=e10]:
      - generic [ref=e11]:
        - generic [ref=e12]: Log in to your account
        - generic [ref=e13]: Enter your email and password below to log in
      - generic [ref=e14]:
        - generic [ref=e15]:
          - generic [ref=e16]: Email address
          - textbox "Email address" [active] [ref=e18]:
            - /placeholder: email@example.com
            - text: admin@testing.com
        - generic [ref=e19]:
          - generic [ref=e20]:
            - generic [ref=e21]: Password
            - generic [ref=e22]:
              - textbox "Password" [ref=e23]
              - button "Toggle password visibility" [ref=e25]:
                - img [ref=e26]
          - link "Forgot your password?" [ref=e29] [cursor=pointer]:
            - /url: http://laravel_monitor.test/forgot-password
        - generic [ref=e30]:
          - checkbox "Remember me" [ref=e31]
          - generic [ref=e33]: Remember me
        - button "Log in" [ref=e35]:
          - img [ref=e37]
          - generic [ref=e40]: Log in
      - generic [ref=e41]:
        - text: Don't have an account?
        - link "Sign up" [ref=e42] [cursor=pointer]:
          - /url: http://laravel_monitor.test/register
  - generic:
    - status
```

# Test source

```ts
  1  | import { test, expect } from '@playwright/test';
  2  | 
  3  | test.describe('Full End-to-End CRUD Flow', () => {
  4  |   test.describe.configure({ mode: 'serial' });
  5  | 
  6  |   let page;
  7  |   const timestamp = Date.now();
  8  |   const testData = {
  9  |     productName: `E2E Product ${timestamp}`,
  10 |     productSlug: `e2e-product-${timestamp}`,
  11 |     productUpdatedName: `E2E Product Updated ${timestamp}`,
  12 |     planName: `E2E Plan ${timestamp}`,
  13 |     planSlug: `e2e-plan-${timestamp}`,
  14 |     licenseKey: '',
  15 |   };
  16 | 
  17 |   test.beforeAll(async ({ browser }) => {
  18 |     page = await browser.newPage();
  19 |     await page.goto('/login');
  20 |     await page.getByLabel('Email address').fill('admin@testing.com');
> 21 |     await page.getByLabel('Password').fill('password');
     |                                       ^ Error: locator.fill: Error: strict mode violation: getByLabel('Password') resolved to 2 elements:
  22 |     await page.click('button[type="submit"]');
  23 |     await page.waitForURL('/dashboard');
  24 |   });
  25 | 
  26 |   test.afterAll(async () => {
  27 |     await page.close();
  28 |   });
  29 | 
  30 |   test('Step 1: Product CRUD', async () => {
  31 |     await page.goto('/products/create');
  32 |     await page.getByLabel('Name').fill(testData.productName);
  33 |     // Slug might auto-fill via debounce, but we fill it to be safe
  34 |     await page.getByLabel('Slug').fill(testData.productSlug);
  35 |     await page.getByLabel('Description').fill('E2E Test Description');
  36 |     await page.click('button[type="submit"]');
  37 |     
  38 |     await page.waitForURL('/products');
  39 |     await expect(page.locator(`text=${testData.productName}`).first()).toBeVisible();
  40 | 
  41 |     await page.locator(`tr:has-text("${testData.productName}") >> a[href*="/edit"]`).first().click();
  42 |     
  43 |     await page.getByLabel('Name').fill(testData.productUpdatedName);
  44 |     await page.click('button[type="submit"]');
  45 |     
  46 |     await page.waitForURL('/products');
  47 |     await expect(page.locator(`text=${testData.productUpdatedName}`).first()).toBeVisible();
  48 |   });
  49 | 
  50 |   test('Step 2: Plan CRUD', async () => {
  51 |     await page.goto('/plans/create');
  52 |     
  53 |     await page.getByLabel('Product').selectOption({ label: testData.productUpdatedName });
  54 |     await page.getByLabel('Plan Name').fill(testData.planName);
  55 |     await page.getByLabel('Slug').fill(testData.planSlug);
  56 |     await page.getByLabel('Monthly Price (IDR)').fill('100000');
  57 |     await page.getByLabel('Max Devices').fill('5');
  58 |     await page.click('button[type="submit"]');
  59 |     
  60 |     await page.waitForURL('/plans');
  61 |     await expect(page.locator(`text=${testData.planName}`).first()).toBeVisible();
  62 |   });
  63 | 
  64 |   test('Step 3: License CRUD', async () => {
  65 |     await page.goto('/licenses/create');
  66 |     await page.getByLabel('Product').selectOption({ label: testData.productUpdatedName });
  67 |     await page.waitForTimeout(1000); // Wait for Livewire
  68 |     await page.getByLabel('Plan').selectOption({ label: testData.planName });
  69 |     
  70 |     await page.getByLabel('Assign to User').selectOption({ label: 'Super Admin' });
  71 |     await page.getByLabel('Max Devices').fill('5');
  72 |     await page.click('button[type="submit"]');
  73 |     
  74 |     await page.waitForURL('/licenses');
  75 |     await expect(page.locator('table')).toContainText(testData.planName);
  76 |   });
  77 | 
  78 |   test('Step 4: Cleanup (Delete)', async () => {
  79 |     await page.goto('/plans');
  80 |     await page.locator(`tr:has-text("${testData.planName}") >> button[wire\\:click*="confirmDelete"]`).first().click();
  81 |     await page.locator('button[type="submit"]:has-text("Delete")').click();
  82 |     await expect(page.locator(`text=${testData.planName}`)).toHaveCount(0);
  83 |   });
  84 | });
  85 | 
```