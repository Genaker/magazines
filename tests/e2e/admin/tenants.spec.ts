import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../helpers/auth';
import { setTenantSubdomainsEnabled, tenantSeed, tenantUrl } from '../helpers/tenant';

test.describe.configure({ timeout: 60_000 });

test.beforeAll(() => {
  setTenantSubdomainsEnabled(true);
});

test.afterAll(() => {
  setTenantSubdomainsEnabled(false);
});

test('super admin sees seeded tenants and can create another tenant', async ({ page }) => {
  await loginAsAdmin(page, tenantSeed.apexHost);

  await page.goto(tenantUrl(tenantSeed.apexHost, '/admin/tenants'));
  await expect(page.getByRole('heading', { name: 'Tenants' })).toBeVisible();
  await expect(page.getByRole('cell', { name: 'Tenant 1' })).toBeVisible();
  await expect(page.getByRole('cell', { name: 'Default', exact: true })).toBeVisible();

  await page.getByRole('link', { name: 'New tenant' }).click();
  const slug = `e2e-${Date.now()}`;
  await page.locator('#name').fill('E2E Tenant');
  await page.locator('#slug').fill(slug);
  await page.locator('#host').fill(`${slug}.test`);
  await page.getByRole('button', { name: 'Create' }).click();

  await expect(page.getByText('Tenant created.')).toBeVisible();
  await expect(page.getByRole('cell', { name: 'E2E Tenant' })).toBeVisible();
});
