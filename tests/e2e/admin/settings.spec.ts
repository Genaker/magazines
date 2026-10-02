import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../helpers/auth';

test.describe.configure({ timeout: 60_000 });

test('super admin can update site settings', async ({ page }) => {
  const tagline = `E2E tagline ${Date.now()}`;

  await loginAsAdmin(page);
  await page.goto('/admin/settings');

  await expect(page.getByRole('heading', { name: 'Home page' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Language' })).toBeVisible();

  await page.locator('input[name="site_tagline"]').fill(tagline);
  await page.getByRole('button', { name: 'Save' }).click();

  await expect(page.getByText('Settings saved.')).toBeVisible();

  await page.goto('/');
  await expect(page.getByText(tagline)).toBeVisible();
});
