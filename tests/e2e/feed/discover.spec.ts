import { test, expect } from '@playwright/test';

test('legacy discover url redirects to home', async ({ page }) => {
  await page.goto('/discover');

  await expect(page).toHaveURL('/');
  await expect(page.getByRole('heading', { name: 'Home' })).toBeVisible();
});
