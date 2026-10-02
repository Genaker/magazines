import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';

test('author can rename and delete a custom reading list', async ({ page }) => {
  const listName = `Temp list ${Date.now()}`;
  const renamed = `Renamed ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto('/me/lists');
  await page.locator('#list-name').fill(listName);
  await page.getByRole('button', { name: 'Create list' }).click();

  await page.locator('summary', { hasText: 'Edit list' }).click();
  await page.locator('#edit-list-name').fill(renamed);
  await page.getByRole('button', { name: 'Save' }).click();
  await expect(page.locator('main h1')).toHaveText(renamed);

  await page.locator('summary', { hasText: 'Edit list' }).click();
  page.once('dialog', (dialog) => dialog.accept());
  await page.getByRole('button', { name: 'Delete list' }).click();

  await expect(page).toHaveURL(/\/me\/lists$/);
  await expect(page.locator('main')).not.toContainText(renamed);
});
