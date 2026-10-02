import { test, expect } from '@playwright/test';
import { navWriteLink } from '../helpers/auth';

test('user can sign in with password from magic link login page', async ({ page }) => {
  await page.goto('/login');

  await expect(page.getByRole('button', { name: 'Email me a link' })).toBeVisible();
  await page.getByRole('link', { name: 'Use password instead' }).click();

  await expect(page).toHaveURL(/\/login\/password$/);
  await page.getByLabel('Email').fill('author@magazines.test');
  await page.getByLabel('Password').fill('password');
  await page.getByRole('button', { name: /log in/i }).click();

  await expect(navWriteLink(page)).toBeVisible();
});
