import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { seedPosts } from '../helpers/seed';

test('author stats page shows audience and story metrics', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/me/stats');

  await expect(page.locator('main h1')).toHaveText('Stats');
  await expect(page.getByRole('heading', { name: 'Monthly' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Audience' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Lifetime', exact: true })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'By story (lifetime)' })).toBeVisible();
  await expect(page.getByLabel('Month')).toBeVisible();
  await expect(page.getByText('Email subscribers')).toBeVisible();
  await expect(page.getByText('Total views')).toBeVisible();
  await expect(page.getByRole('heading', { name: 'By story (lifetime)' }).locator('..').getByRole('link', { name: seedPosts.whyWriting.title })).toBeVisible();
});

test('stats link appears in profile sidebar', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/profile');

  await page.getByRole('navigation', { name: 'Profile menu' }).getByRole('link', { name: 'Stats' }).click();
  await page.waitForURL(/\/me\/stats$/);
  await expect(page.locator('main h1')).toHaveText('Stats');
});
