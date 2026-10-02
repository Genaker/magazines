import { test, expect } from '@playwright/test';

test('authors index is reachable from navigation', async ({ page }) => {
  await page.goto('/');

  await page.locator('nav').getByRole('link', { name: 'Authors' }).click();

  await expect(page).toHaveURL(/\/authors$/);
  await expect(page.getByRole('heading', { name: 'Authors' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Demo Author' })).toBeVisible();
});

test('authors index search filters the list', async ({ page }) => {
  await page.goto('/authors', { waitUntil: 'domcontentloaded' });

  const searchInput = page.locator('main input[name="q"]');
  await expect(searchInput).toBeVisible();
  await searchInput.fill('Demo');
  await searchInput.press('Enter');

  await expect(page).toHaveURL(/\/authors\?q=Demo/);
  await expect(page.getByRole('main').getByRole('link', { name: 'Demo Author' })).toBeVisible();
  await expect(page.getByRole('main').getByRole('link', { name: 'Super Admin' })).toHaveCount(0);
});
