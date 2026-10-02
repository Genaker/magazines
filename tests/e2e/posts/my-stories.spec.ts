import { test, expect } from '@playwright/test';
import { loginAsTechWriter } from '../helpers/auth';
import { seedPosts } from '../helpers/seed';

test('my stories lists seeded draft and published posts', async ({ page }) => {
  await loginAsTechWriter(page);
  await page.goto('/me/posts');

  await expect(page.getByRole('heading', { name: 'My stories' })).toBeVisible();
  await expect(page.getByRole('link', { name: seedPosts.laravel.title })).toBeVisible();
  await expect(page.getByRole('link', { name: /Draft: The Future of Local AI|Admin Edited Draft/ })).toBeVisible();
});
