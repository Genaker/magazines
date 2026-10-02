import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';

test('author can update profile bio and website', async ({ page }) => {
  const bio = `E2E bio updated at ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto('/profile');

  await page.getByLabel('Bio').fill(bio);
  await page.getByLabel('Website').fill('https://example.com');
  await page.locator('form[action*="profile"]').getByRole('button', { name: 'Save' }).click();

  await expect(page.getByText('Saved.')).toBeVisible();

  await page.goto('/@demoauthor?tab=about');
  await expect(page.getByText(bio).first()).toBeVisible();
  await expect(page.getByRole('link', { name: 'Website' }).first()).toBeVisible();
});
