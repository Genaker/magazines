import { test, expect } from '@playwright/test';
import { seedPosts, seedTags } from '../helpers/seed';

test('tag page lists posts with that tag', async ({ page }) => {
  await page.goto(seedTags.writing.path);

  await expect(page.getByRole('heading', { name: seedTags.writing.heading })).toBeVisible();
  await expect(page.getByRole('link', { name: seedPosts.whyWriting.title }).first()).toBeVisible();
});
