import { test, expect } from '@playwright/test';
import { loginAsTechWriter } from '../helpers/auth';
import { seedPosts } from '../helpers/seed';

test('guest cannot view draft post by direct link', async ({ page }) => {
  const response = await page.goto(seedPosts.draftLocalAi.path);

  expect(response?.status()).toBe(403);
});

test('author can view own draft by direct link', async ({ page }) => {
  await loginAsTechWriter(page);
  await page.goto(seedPosts.draftLocalAi.path);

  await expect(page.getByText(/shareable by link/)).toBeVisible();
  await expect(page.locator('article h1')).toBeVisible();
});
