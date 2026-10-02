import { test, expect } from '@playwright/test';
import { navWriteLink } from '../helpers/auth';

test('new user can register and is sent to sign-in code step', async ({ page }) => {
  const stamp = Date.now();

  await page.goto('/register');
  await page.locator('#name').fill('E2E New User');
  await page.locator('#username').fill(`e2euser${stamp}`);
  await page.locator('#email').fill(`e2e${stamp}@example.com`);
  await page.locator('#password').fill('password');
  await page.locator('#password_confirmation').fill('password');
  await page.getByRole('button', { name: /register/i }).click();

  await expect(page).toHaveURL(/\/login/);
  await expect(page.locator('#code')).toBeVisible();
  await expect(page.getByRole('button', { name: 'Sign in' })).toBeVisible();
  await expect(navWriteLink(page)).toHaveCount(0);
});
