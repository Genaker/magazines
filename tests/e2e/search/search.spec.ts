import { test, expect } from '@playwright/test';
import { seedPosts } from '../helpers/seed';

test('search finds posts, authors, and magazines', async ({ page }) => {
  await page.goto('/search?q=writing');

  await expect(page.getByRole('heading', { name: 'Search' })).toBeVisible();
  await expect(page.getByRole('link', { name: seedPosts.whyWriting.title })).toBeVisible();

  await page.goto('/search?q=demoauthor');
  await expect(page.getByRole('heading', { name: 'Authors' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Demo Author (@demoauthor)' })).toBeVisible();

  await page.goto('/search?q=Commons');
  await expect(page.getByRole('heading', { name: 'Magazines' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'The Commons' })).toBeVisible();
});
