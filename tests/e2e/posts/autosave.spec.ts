import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { fillTinyMceBody, waitForAutosave, waitForTinyMce } from '../helpers/editor';

test('autosave persists a new draft while writing', async ({ page }) => {
  const title = `E2E Autosave ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto('/write');
  await waitForTinyMce(page);

  await page.locator('input[name="title"]').fill(title);
  await fillTinyMceBody(page, 'Autosaved draft content.');

  await waitForAutosave(page);

  await page.goto('/me/posts');
  await expect(page.getByText(title)).toBeVisible();
});
