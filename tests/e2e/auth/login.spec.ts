import { test, expect } from '@playwright/test';

test('login page defaults to magic link email step', async ({ page }) => {
  await page.goto('/login');

  await expect(page.getByRole('button', { name: 'Email me a link' })).toBeVisible();
  await expect(page.getByLabel('Email')).toBeVisible();
  await expect(page.getByRole('link', { name: 'Use password instead' })).toBeVisible();
});
