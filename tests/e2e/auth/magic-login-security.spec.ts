import { test, expect } from '@playwright/test';
import { navWriteLink } from '../helpers/auth';
import { seedMagicLoginCode } from '../helpers/magic-link';
import {
  clearE2eApplicationCache,
  seedUnverifiedUser,
  unlockMagicLogin,
} from '../helpers/passwordless-auth';

test('registration keeps user logged out until sign-in code is entered', async ({ page }) => {
  const stamp = Date.now();
  const email = `guest-reg${stamp}@example.com`;

  await page.goto('/register');
  await page.locator('#name').fill('E2E Guest Register');
  await page.locator('#username').fill(`guestreg${stamp}`);
  await page.locator('#email').fill(email);
  await page.getByRole('checkbox', { name: /magic link/i }).check();
  await page.getByRole('button', { name: /register/i }).click();

  await expect(page).toHaveURL(/\/login/);
  await expect(page.locator('#code')).toBeVisible();
  await expect(navWriteLink(page)).toHaveCount(0);
});

test('unverified user can request magic sign-in code and verify email on login', async ({ page }) => {
  const stamp = Date.now();
  const email = `unverified${stamp}@example.com`;

  seedUnverifiedUser(email, `unverified${stamp}`);

  await page.goto('/login');
  await page.locator('#email').fill(email);
  await page.getByRole('button', { name: 'Email me a link' }).click();

  const devCode = page.locator('p.font-mono.text-3xl');
  await expect(devCode).toBeVisible();
  await page.locator('#code').fill((await devCode.textContent())!.trim());
  await page.getByRole('button', { name: 'Sign in' }).click();

  await expect(navWriteLink(page)).toBeVisible();
});

test('repeated wrong magic codes lock sign-in until unlocked', async ({ page }) => {
  const stamp = Date.now();
  const email = `lockout${stamp}@example.com`;
  const username = `lockout${stamp}`;

  seedUnverifiedUser(email, username);
  seedMagicLoginCode(email, '998877');

  await page.goto('/login');
  await page.locator('#email').fill(email);
  await page.getByRole('button', { name: 'Email me a link' }).click();

  for (let i = 0; i < 5; i++) {
    await page.locator('#code').fill('000000');
    await page.getByRole('button', { name: 'Sign in' }).click();
  }

  await page.locator('#code').fill('000000');
  await page.getByRole('button', { name: 'Sign in' }).click();

  await expect(page.getByText(/too many failed sign-in attempts/i)).toBeVisible();

  await page.goto('/login?change_email=1');
  await page.locator('#email').fill(email);
  await page.getByRole('button', { name: 'Email me a link' }).click();
  await expect(page.getByText(/too many failed sign-in attempts/i)).toBeVisible();

  unlockMagicLogin(email);

  await page.goto('/login');
  await page.locator('#email').fill(email);
  await page.getByRole('button', { name: 'Email me a link' }).click();

  const devCode = page.locator('p.font-mono.text-3xl');
  await expect(devCode).toBeVisible();
  await page.locator('#code').fill((await devCode.textContent())!.trim());
  await page.getByRole('button', { name: 'Sign in' }).click();

  await expect(navWriteLink(page)).toBeVisible();

  clearE2eApplicationCache();
});
