import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { fillTinyMceBody, waitForTinyMce } from '../helpers/editor';

test('author can add up to ten tags on a published post', async ({ page }) => {
  const title = `E2E Ten Tags ${Date.now()}`;
  const tags = Array.from({ length: 12 }, (_, i) => `tag${i + 1}`).join(', ');

  await loginAsAuthor(page);
  await page.goto('/write');
  await waitForTinyMce(page);

  await page.locator('input[name="title"]').fill(title);
  await fillTinyMceBody(page, 'Story with many tags.');
  await page.locator('input[name="tags"]').fill(tags);
  await page.locator('select[name="status"]').selectOption('published');
  await page.getByRole('button', { name: 'Publish' }).click();

  await expect(page.getByRole('heading', { name: title })).toBeVisible();

  for (let i = 1; i <= 10; i++) {
    await expect(page.getByRole('link', { name: `#tag${i}`, exact: true })).toBeVisible();
  }

  await expect(page.getByRole('link', { name: '#tag11', exact: true })).not.toBeVisible();
});
