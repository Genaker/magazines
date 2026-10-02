import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { fillTinyMceBody, waitForTinyMce } from '../helpers/editor';

test('author can create unlisted post shareable by link', async ({ page }) => {
  const title = `E2E Unlisted ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto('/write');
  await waitForTinyMce(page);

  await page.locator('input[name="title"]').fill(title);
  await fillTinyMceBody(page, 'Unlisted story body.');
  await page.locator('select[name="status"]').selectOption('unlisted');
  await page.getByRole('button', { name: 'Publish' }).click();

  const shareUrl = page.url();

  await page.getByRole('button', { name: 'Logout' }).click();

  await page.goto(shareUrl);
  await expect(page.getByText('unlisted — shareable by link')).toBeVisible();
  await expect(page.getByRole('heading', { name: title })).toBeVisible();
});
