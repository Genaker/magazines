import { test, expect } from '@playwright/test';
import { login, loginAsAuthor } from '../helpers/auth';
import { expectStatusAlert } from '../helpers/messages';

test('magazine owner can reject a join request', async ({ page }) => {
  const magazineName = `E2E Reject Join ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto('/magazines/create');
  await page.locator('input[name="name"]').fill(magazineName);
  await page.getByRole('button', { name: 'Create magazine' }).click();

  await page.getByRole('button', { name: 'Logout' }).click();
  await login(page, 'admin@magazines.test');
  await page.goto('/magazines');
  await page.getByRole('link', { name: magazineName }).click();

  await page.getByText('Request to join').click();
  await page.locator('textarea[name="message"]').fill('Please let me join.');
  await page.getByRole('button', { name: 'Send request' }).click();
  await expect(page.getByText('Join request pending')).toBeVisible();

  await page.getByRole('button', { name: 'Logout' }).click();
  await loginAsAuthor(page);
  await page.goto('/magazines');
  await page.getByRole('link', { name: magazineName }).click();
  await page.getByRole('link', { name: 'Manage magazine' }).click();

  await expect(page.getByText('Super Admin')).toBeVisible();
  await page.getByRole('button', { name: 'Reject' }).first().click();
  await expectStatusAlert(page, 'Join request rejected.');
});
