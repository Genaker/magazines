import { test, expect } from '@playwright/test';
import { fillTinyMceBody, waitForTinyMce } from '../helpers/editor';
import {
  loginAsSuperAdminOnDefault,
  loginAsTenantAdmin,
  loginAsTenantAuthor,
  setTenantSubdomainsEnabled,
  tenantAuthorSubdomainUrl,
  tenantMagazineSubdomainUrl,
  tenantSeed,
  tenantUrl,
} from '../helpers/tenant';

test.describe.configure({ mode: 'serial', timeout: 120_000 });

test.beforeAll(() => {
  setTenantSubdomainsEnabled(true);
});

test.afterAll(() => {
  setTenantSubdomainsEnabled(false);
});

test('tenant1 home shows seeded posts and hides default tenant content', async ({ page }) => {
  await page.goto(tenantUrl(tenantSeed.host, '/'));

  await expect(page.getByRole('link', { name: tenantSeed.welcomePostTitle }).first()).toBeVisible();
  await expect(page.getByRole('link', { name: 'Getting Started with Laravel' })).toHaveCount(0);
});

test('default tenant home shows default seed posts only', async ({ page }) => {
  await page.goto(tenantUrl(tenantSeed.defaultHost, '/'));

  await expect(page.getByRole('link', { name: 'Getting Started with Laravel' }).first()).toBeVisible();
  await expect(page.getByRole('link', { name: tenantSeed.welcomePostTitle })).toHaveCount(0);
});

test('author subdomain on tenant1 serves profile and seed stories', async ({ page }) => {
  await page.goto(tenantAuthorSubdomainUrl('/'));

  await expect(page.getByRole('heading', { name: 'Tenant One Author' }).first()).toBeVisible();
  await expect(page.getByRole('link', { name: tenantSeed.welcomePostTitle }).first()).toBeVisible();
});

test('magazine subdomain on tenant1 serves seeded magazine and featured post', async ({ page }) => {
  await page.goto(tenantMagazineSubdomainUrl('/'));

  await expect(page.getByRole('heading', { name: tenantSeed.magazineName })).toBeVisible();
  await expect(page.getByRole('link', { name: tenantSeed.welcomePostTitle }).first()).toBeVisible();
});

test('tenant author can create and publish a story on tenant1 domain', async ({ page }) => {
  const title = `E2E Tenant Post ${Date.now()}`;

  await loginAsTenantAuthor(page);
  await page.goto(tenantUrl(tenantSeed.host, '/write'));
  await waitForTinyMce(page);

  await page.locator('input[name="title"]').fill(title);
  await page.locator('input[name="subtitle"]').fill('Published from tenant1.lvh.me');
  await fillTinyMceBody(page, 'Tenant e2e publish body.');
  await page.locator('select[name="status"]').selectOption('published');
  await page.getByRole('button', { name: 'Publish' }).click();

  await expect(page.getByRole('heading', { name: title })).toBeVisible();
  expect(page.url()).toContain(`${tenantSeed.authorUsername}.${tenantSeed.host}`);

  await page.goto(tenantUrl(tenantSeed.host, '/'));
  await expect(page.getByRole('link', { name: title }).first()).toBeVisible();
});

test('tenant admin can open admin on tenant domain', async ({ page }) => {
  await loginAsTenantAdmin(page);

  await page.goto(tenantUrl(tenantSeed.host, '/admin/magazines/manage'));
  await expect(page.getByRole('heading', { name: 'Magazines' })).toBeVisible();
  await expect(page.getByText(tenantSeed.magazineName)).toBeVisible();
});

test('super admin can manage seeded tenants from apex admin host', async ({ page }) => {
  await loginAsSuperAdminOnDefault(page);

  await page.goto(tenantUrl(tenantSeed.apexHost, '/admin/tenants'));
  await expect(page.getByRole('heading', { name: 'Tenants' })).toBeVisible();
  await expect(page.getByRole('cell', { name: 'Tenant 1' })).toBeVisible();
  await expect(page.getByRole('cell', { name: 'Default', exact: true })).toBeVisible();
});

test('tenant author can create a magazine on tenant1 domain', async ({ page }) => {
  const magazineName = `E2E Tenant Magazine ${Date.now()}`;

  await loginAsTenantAuthor(page);
  await page.goto(tenantUrl(tenantSeed.host, '/magazines/create'));

  await page.locator('input[name="name"]').fill(magazineName);
  await page.locator('textarea[name="description"]').fill('Magazine created during tenant e2e.');
  await page.getByRole('button', { name: 'Create magazine' }).click();

  await expect(page.getByRole('heading', { name: magazineName })).toBeVisible();
});
