import { test, expect } from '@playwright/test';

test('nav search submits query to search results page', async ({ page }) => {
  await page.setViewportSize({ width: 1280, height: 720 });
  await page.goto('/');

  await page.locator('nav input[name="q"]').fill('writing');
  await page.locator('nav input[name="q"]').press('Enter');

  await expect(page).toHaveURL(/\/search\?q=writing/);
  await expect(page.locator('main')).toContainText('Search');
});
