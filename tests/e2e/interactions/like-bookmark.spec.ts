import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { seedPosts } from '../helpers/seed';

test('guest can like and unlike a post', async ({ page }) => {
  await page.goto(seedPosts.laravel.path);

  const likeBtn = page.locator('#like-btn');
  const likeCount = page.locator('#like-count');
  const initialCount = Number(await likeCount.textContent());

  await likeBtn.click();
  await expect(page.locator('#like-label')).toHaveText('Unlike');
  await expect.poll(async () => Number(await likeCount.textContent())).toBe(initialCount + 1);

  await likeBtn.click();
  await expect(page.locator('#like-label')).toHaveText('Like');
  await expect.poll(async () => Number(await likeCount.textContent())).toBe(initialCount);
});

test('author can bookmark and unbookmark a post', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto(seedPosts.whyWriting.path);

  const bookmarkBtn = page.locator('#bookmark-btn');
  await bookmarkBtn.click();
  await page.locator('#list-picker-items label').filter({ hasText: 'Reading list' }).click();
  await expect(page.locator('#bookmark-label')).toHaveText('Saved');

  await page.goto('/me/lists');
  await expect(page.locator('main h1')).toHaveText('Reading lists');
  await page.locator('main a.rounded-lg').filter({ hasText: 'Reading list' }).click();
  await expect(page.locator('main')).toContainText(seedPosts.whyWriting.title);

  await page.goto(seedPosts.whyWriting.path);
  await bookmarkBtn.click();
  await page.locator('#list-picker-items label').filter({ hasText: 'Reading list' }).click();
  await expect(page.locator('#bookmark-label')).toHaveText('Save');

  await page.goto('/me/lists');
  await page.locator('main a.rounded-lg').filter({ hasText: 'Reading list' }).click();
  await expect(page.locator('main')).toContainText('No stories in this list yet.');
});
