import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';

test('author can create a custom reading list', async ({ page }) => {
  const listName = `Weekend reads ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto('/me/lists');

  await expect(page.locator('main h1')).toHaveText('Reading lists');
  await page.locator('#list-name').fill(listName);
  await page.getByRole('button', { name: 'Create list' }).click();

  await expect(page).toHaveURL(/\/me\/lists\/\d+$/);
  await expect(page.locator('main h1')).toHaveText(listName);
  await expect(page.locator('main')).toContainText('No stories in this list yet.');
});
