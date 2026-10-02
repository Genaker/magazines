import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { fillTinyMceBody, waitForTinyMce } from '../helpers/editor';

test('author can delete a draft story', async ({ page }) => {
  const title = `E2E Delete ${Date.now()}`;

  page.on('dialog', (dialog) => dialog.accept());

  await loginAsAuthor(page);
  await page.goto('/write');
  await waitForTinyMce(page);

  await page.locator('input[name="title"]').fill(title);
  await fillTinyMceBody(page, 'Story to delete.');
  await page.locator('select[name="status"]').selectOption('draft');
  await page.getByRole('button', { name: 'Publish' }).click();

  await page.getByRole('link', { name: 'Edit' }).click();
  await page.getByRole('button', { name: 'Delete' }).click();

  await page.goto('/me/posts');
  await expect(page.getByText(title)).not.toBeVisible();
});
