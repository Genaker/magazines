import { test, expect } from '@playwright/test';
import { login, navWriteLink } from '../helpers/auth';
import { seedPosts } from '../helpers/seed';

test('home page shows site-wide story sections for guests', async ({ page }) => {
  await page.goto('/');

  await expect(page).toHaveURL('/');
  await expect(page.getByRole('heading', { name: 'Home' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Popular tags' })).toBeVisible();
  await expect(page.getByRole('link', { name: /#laravel/i }).first()).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Top stories this week' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Trending today' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Latest' })).toBeVisible();
  await expect(page.getByRole('link', { name: seedPosts.laravel.title }).first()).toBeVisible();
});

test('authenticated user sees personalized feed sections on home', async ({ page }) => {
  await login(page, 'author@magazines.test');
  await page.goto('/category/web-development');

  const followBtn = page.locator('#follow-category-btn');
  await expect(followBtn).toBeVisible();

  if (await followBtn.getAttribute('data-following') === '0') {
    await followBtn.click();
    await expect(followBtn).toHaveText('Following');
  }

  await page.goto('/');

  await expect(page.getByRole('heading', { name: 'Home' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Latest from your feed' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Popular in your feed' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Top stories this week' })).toBeVisible();
  await expect(navWriteLink(page)).toBeVisible();
});
