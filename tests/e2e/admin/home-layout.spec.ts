import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../helpers/auth';
import { configureSiteSettings, resetSiteSettings } from '../helpers/admin-settings';

test.describe.configure({ timeout: 60_000 });

test.afterEach(async ({ page }) => {
  await resetSiteSettings(page);
});

test('super admin can set latest-only home layout', async ({ page }) => {
  await configureSiteSettings(page, { homeLayout: 'latest' });

  await page.goto('/');

  await expect(page.getByRole('heading', { name: 'Home' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Latest' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Top stories this week' })).not.toBeVisible();
  await expect(page.getByRole('heading', { name: 'Trending last hour' })).not.toBeVisible();
});

test('super admin can set trending-only home layout', async ({ page }) => {
  await configureSiteSettings(page, { homeLayout: 'trending' });

  await page.goto('/');

  await expect(page.getByRole('heading', { name: 'Top stories this week' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Trending last hour' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Trending today' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Latest' })).not.toBeVisible();
});

test('admin settings page shows home layout options', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('/admin/settings');

  await expect(page.getByRole('heading', { name: 'Home page' })).toBeVisible();
  await expect(page.locator('#home_layout')).toBeVisible();
  await expect(page.locator('#home_layout option[value="latest"]')).toHaveText('Latest only');
});
