import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { seedPosts } from '../helpers/seed';

test('published post page shows content and interactions', async ({ page }) => {
  await page.goto(seedPosts.whyWriting.path);

  await expect(page.getByRole('heading', { name: seedPosts.whyWriting.title })).toBeVisible();
  await expect(page.getByText(seedPosts.whyWriting.subtitle)).toBeVisible();
  await expect(page.locator('#like-btn')).toBeVisible();
  await expect(page.locator('#reading-progress')).toBeAttached();
});

test('published post shows discussion section for logged-in users', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto(seedPosts.whyWriting.path);

  await expect(page.locator('#comments')).toBeVisible();
  await expect(page.getByLabel('Join the discussion')).toBeVisible();
  await expect(page.locator('#comment-body')).toBeVisible();
});
