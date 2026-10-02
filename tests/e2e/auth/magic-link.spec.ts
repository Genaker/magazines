import { test, expect } from '@playwright/test';
import { seedMagicLoginCode } from '../helpers/magic-link';
import { navWriteLink } from '../helpers/auth';

test('magic link request shows code entry step', async ({ page }) => {
  await page.goto('/login');

  await page.locator('#email').fill('author@magazines.test');
  await page.getByRole('button', { name: 'Email me a link' }).click();

  await expect(page.getByText('Enter the 6-digit code from your email.')).toBeVisible();
  await expect(page.getByLabel('6-digit sign-in code')).toBeVisible();
  await expect(page.locator('form').filter({ has: page.locator('#code') }).locator('input[name="email"][type="hidden"]')).toHaveCount(1);
  await expect(page.getByRole('button', { name: 'Resend sign-in email' })).toBeVisible();
  await expect(page.getByText("Didn't get the email or need a new code? Resend anytime.")).toBeVisible();
});

test('resend sign-in email shows confirmation on code step', async ({ page }) => {
  await page.goto('/login');

  await page.locator('#email').fill('author@magazines.test');
  await page.getByRole('button', { name: 'Email me a link' }).click();
  await page.getByRole('button', { name: 'Resend sign-in email' }).click();

  await expect(page.getByText('If that account exists, we sent a new sign-in link and code.')).toBeVisible();
  await expect(page.getByLabel('6-digit sign-in code')).toBeVisible();
});

test('six digit code verification logs user in', async ({ page }) => {
  const code = seedMagicLoginCode('author@magazines.test', '445566');

  await page.goto('/login');
  await page.locator('#email').fill('author@magazines.test');
  await page.getByRole('button', { name: 'Email me a link' }).click();

  await page.locator('#code').fill(code);
  await page.getByRole('button', { name: 'Sign in' }).click();

  await expect(navWriteLink(page)).toBeVisible();
  await expect(page.getByRole('navigation').getByRole('link', { name: 'Profile', exact: true })).toBeVisible();
});
