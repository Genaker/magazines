import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';

test('author can submit a category request', async ({ page }) => {
  const categoryName = `E2E Category ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto('/category/request');

  await expect(page).toHaveURL(/\/category\/request$/);
  await expect(page.locator('main h1')).toHaveText('Request a category');
  await page.locator('input[name="name"]').fill(categoryName);
  await page.locator('textarea[name="reason"]').fill('This category is needed for e2e testing coverage.');
  await page.getByRole('button', { name: 'Submit request' }).click();

  await expect(page).toHaveURL(/\/me\/category-requests$/);
  await expect(page.locator('main')).toContainText('My category requests');
  await expect(page.locator('main')).toContainText(categoryName);
  await expect(page.locator('main')).toContainText('Status: pending');
});
