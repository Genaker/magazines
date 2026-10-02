import { test, expect } from '@playwright/test';
import { seedPosts } from '../helpers/seed';

test('post page displays view count', async ({ page }) => {
  await page.goto(seedPosts.whyWriting.path);

  const viewCount = page.locator('#view-count');
  await expect(viewCount).toBeVisible();
  await expect(viewCount).toHaveText(/\d+/);
});
