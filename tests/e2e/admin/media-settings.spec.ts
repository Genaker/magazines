import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../helpers/auth';

test.describe.configure({ timeout: 60_000 });

test('super admin can view and save image settings', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('/admin/settings');

  await expect(page.getByRole('heading', { name: 'Images', exact: true })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Post images' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Gallery images' })).toBeVisible();

  await expect(page.locator('#media_post_max_width')).toHaveValue('1500');
  await expect(page.locator('#media_post_jpeg_quality')).toHaveValue('85');

  await page.locator('input[type="checkbox"][name="media_post_do_not_resize"]').check();
  await page.getByRole('button', { name: 'Save' }).click();
  await expect(page.getByText('Settings saved.')).toBeVisible();

  await page.reload();
  await expect(page.locator('input[type="checkbox"][name="media_post_do_not_resize"]')).toBeChecked();

  await page.locator('input[type="checkbox"][name="media_post_do_not_resize"]').uncheck();
  await page.getByRole('button', { name: 'Save' }).click();
  await expect(page.getByText('Settings saved.')).toBeVisible();
});
