import { test, expect } from '@playwright/test';
import { loginAsAuthor, navLogoutButton, navWriteLink } from '../helpers/auth';

test('logged-in user can log out', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/');

  await expect(navWriteLink(page)).toBeVisible();
  await navLogoutButton(page).click();

  await expect(page.getByRole('navigation').getByRole('link', { name: 'Login', exact: true })).toBeVisible();
  await expect(page.getByRole('navigation').getByRole('link', { name: 'Register', exact: true })).toBeVisible();
  await expect(navWriteLink(page)).toHaveCount(0);
});
