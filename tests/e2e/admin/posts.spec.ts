import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../helpers/auth';
import { seedPosts } from '../helpers/seed';

test('admin can edit a draft post title and status', async ({ page }) => {
  const updatedTitle = `Admin Edited Draft ${Date.now()}`;

  await loginAsAdmin(page);
  await page.goto('/admin/posts');

  await page.getByRole('row', { name: new RegExp(seedPosts.draftLocalAi.title) }).getByRole('link', { name: 'Edit' }).click();
  await page.locator('input[name="title"]').fill(updatedTitle);
  await page.getByRole('button', { name: 'Save' }).click();

  await page.goto('/admin/posts');
  await expect(page.getByText(updatedTitle)).toBeVisible();
  await expect(page.getByRole('row', { name: new RegExp(updatedTitle) }).getByText('draft', { exact: true })).toBeVisible();
});
