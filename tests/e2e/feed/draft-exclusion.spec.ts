import { test, expect } from '@playwright/test';
import { seedPosts } from '../helpers/seed';

test('home feed does not list draft posts', async ({ page }) => {
  await page.goto('/');

  await expect(page.getByRole('heading', { name: 'Home' })).toBeVisible();
  await expect(page.getByRole('link', { name: seedPosts.draftLocalAi.title })).not.toBeVisible();
});
