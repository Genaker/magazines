import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { fillTinyMceBody, waitForAutosave, waitForTinyMce } from '../helpers/editor';
import { tinyPngBuffer } from '../helpers/fixtures';

test('cover image upload triggers autosave', async ({ page }) => {
  const title = `E2E Cover Autosave ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto('/write');
  await waitForTinyMce(page);

  await page.locator('input[name="title"]').fill(title);
  await fillTinyMceBody(page, 'Draft with cover image.');

  await page.locator('#cover-image').setInputFiles({
    name: 'cover.png',
    mimeType: 'image/png',
    buffer: tinyPngBuffer(),
  });

  await waitForAutosave(page);

  await page.goto('/me/posts');
  await expect(page.getByText(title)).toBeVisible();
});
