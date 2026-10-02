import { test, expect } from '@playwright/test';

test('popular tags sidebar links to tag pages', async ({ page }) => {
  await page.goto('/');

  await expect(page.getByRole('heading', { name: 'Popular tags' })).toBeVisible();

  const tagLink = page.getByRole('link', { name: /#laravel/i }).first();
  await expect(tagLink).toBeVisible();
  await expect(tagLink).toContainText('(');

  await tagLink.click();
  await expect(page).toHaveURL(/\/tag\/laravel/);
  await expect(page.getByRole('heading', { name: /#laravel/i })).toBeVisible();
});
