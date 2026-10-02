import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { waitForTinyMce } from '../helpers/editor';

test('write page loads with TinyMCE editor and autosave', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/write');

  await expect(page.getByRole('heading', { name: 'Write a story' })).toBeVisible();
  await expect(page.locator('#autosave-status')).toBeAttached();
  await waitForTinyMce(page);
  await expect(page.locator('button[aria-label="Bold"]')).toBeVisible();
  await expect(page.locator('#cover-dropzone')).toBeVisible();
});
