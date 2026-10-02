import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { seedPosts } from '../helpers/seed';

test('logged-in user can like and unlike a post', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto(seedPosts.laravel.path);

  const likeBtn = page.locator('#like-btn');
  const likeCount = page.locator('#like-count');
  const initialCount = Number(await likeCount.textContent());

  await likeBtn.click();
  await expect.poll(async () => Number(await likeCount.textContent())).toBe(initialCount + 1);

  await likeBtn.click();
  await expect.poll(async () => Number(await likeCount.textContent())).toBe(initialCount);
});
