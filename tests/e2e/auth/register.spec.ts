import { test, expect } from '@playwright/test';

test('register page loads with required fields', async ({ page }) => {
  await page.goto('/register');

  await expect(page.locator('#name')).toBeVisible();
  await expect(page.locator('#username')).toBeVisible();
  await expect(page.locator('#email')).toBeVisible();
  await expect(page.locator('#password')).toBeVisible();
  await expect(page.getByRole('button', { name: /register/i })).toBeVisible();
});
