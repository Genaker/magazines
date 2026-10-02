import { test, expect } from '@playwright/test';
import { logoutViaPost, login, navWriteLink } from '../helpers/auth';
import {
  clearE2eApplicationCache,
  seedPasswordlessUser,
  seedPasswordResetPath,
} from '../helpers/passwordless-auth';

test('register page hides password fields when magic link only is checked', async ({ page }) => {
  await page.goto('/register');

  await expect(page.getByRole('checkbox', { name: /magic link/i })).toBeVisible();
  await expect(page.locator('#password')).toBeVisible();

  await page.getByRole('checkbox', { name: /magic link/i }).check();

  await expect(page.locator('#password')).toBeHidden();
  await expect(page.locator('#password_confirmation')).toBeHidden();
  await expect(page.getByText(/create your account without a password/i)).toBeVisible();
});

test('new user can register without password and is sent to sign-in code step', async ({ page }) => {
  const stamp = Date.now();
  const email = `passwordless${stamp}@example.com`;

  await page.goto('/register');
  await page.locator('#name').fill('E2E Passwordless User');
  await page.locator('#username').fill(`pwless${stamp}`);
  await page.locator('#email').fill(email);
  await page.getByRole('checkbox', { name: /magic link/i }).check();
  await page.getByRole('button', { name: /register/i }).click();

  await expect(page).toHaveURL(/\/login/);
  await expect(page.locator('#code')).toBeVisible();
  await expect(page.getByRole('button', { name: 'Sign in' })).toBeVisible();
  await expect(navWriteLink(page)).toHaveCount(0);
});

test('passwordless user can sign in with magic link set password and then use password login', async ({ page }) => {
  const stamp = Date.now();
  const email = `pwless-flow${stamp}@example.com`;
  const username = `pwlessflow${stamp}`;

  clearE2eApplicationCache();
  seedPasswordlessUser(email, username);

  await page.goto('/login');
  await page.locator('#email').fill(email);
  await page.getByRole('button', { name: 'Email me a link' }).click();

  const devCode = page.locator('p.font-mono.text-3xl');
  await expect(devCode).toBeVisible();
  await page.locator('#code').fill((await devCode.textContent())!.trim());
  await page.getByRole('button', { name: 'Sign in' }).click();

  await expect(navWriteLink(page)).toBeVisible();

  await page.goto('/profile');
  await expect(page.getByRole('heading', { name: 'Set Password' })).toBeVisible();
  await page.locator('#update_password_password').fill('e2e-new-password');
  await page.locator('#update_password_password_confirmation').fill('e2e-new-password');
  await page.locator('form').filter({ has: page.locator('#update_password_password') }).getByRole('button', { name: 'Save' }).click();
  await expect(page.getByText('Saved.')).toBeVisible();

  await logoutViaPost(page);

  await login(page, email, 'e2e-new-password');
  await expect(navWriteLink(page)).toBeVisible();
});

test('passwordless user can set password via forgot password reset flow', async ({ page }) => {
  const stamp = Date.now();
  const email = `pwless-reset${stamp}@example.com`;
  const username = `pwlessreset${stamp}`;

  seedPasswordlessUser(email, username);
  const resetPath = seedPasswordResetPath(email);

  await page.goto(resetPath);
  await page.locator('#password').fill('reset-e2e-password');
  await page.locator('#password_confirmation').fill('reset-e2e-password');
  await page.getByRole('button', { name: /reset password/i }).click();

  await expect(page).toHaveURL(/\/login$/);
  await login(page, email, 'reset-e2e-password');
  await expect(navWriteLink(page)).toBeVisible();
});
