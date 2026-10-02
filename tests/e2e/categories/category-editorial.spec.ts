import { test, expect } from '@playwright/test';

test('category front shows editorial sections and rss link', async ({ page }) => {
  await page.goto('/category/technology');

  const main = page.getByRole('main');

  await expect(main.getByRole('heading', { name: 'Technology', level: 1 })).toBeVisible();
  await expect(main.getByRole('link', { name: 'RSS' })).toBeVisible();
  await expect(main.getByRole('heading', { name: 'Top stories this week' })).toBeVisible();
  await expect(main.getByRole('heading', { name: 'Trending last hour' })).toBeVisible();
  await expect(main.getByRole('heading', { name: 'Trending today' })).toBeVisible();
  await expect(main.getByRole('heading', { name: 'Latest' })).toBeVisible();
});

test('category rss feed returns published stories in subtree', async ({ request }) => {
  const response = await request.get('/category/technology/feed.rss');

  expect(response.ok()).toBeTruthy();
  expect(response.headers()['content-type']).toContain('application/xml');

  const body = await response.text();
  expect(body).toContain('<rss version="2.0"');
  expect(body).toContain('Technology ·');
  expect(body).toContain('Getting Started with Laravel');
});

test('magazine category url redirects to magazine page', async ({ page }) => {
  await page.goto('/category/the-commons-essays');

  await expect(page).toHaveURL(/\/magazine\/the-commons\?category=the-commons-essays$/);
  await expect(page.getByRole('main')).toContainText('Essays');
});
