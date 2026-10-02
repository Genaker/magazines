import { test, expect } from '@playwright/test';
import { seedPosts } from '../helpers/seed';

test('category page lists posts in that category', async ({ page }) => {
  await page.goto('/category/writing');

  const main = page.getByRole('main');

  await expect(main.getByRole('heading', { name: 'Writing', level: 1 })).toBeVisible();
  await expect(main.getByRole('link', { name: seedPosts.whyWriting.title }).first()).toBeVisible();
});
