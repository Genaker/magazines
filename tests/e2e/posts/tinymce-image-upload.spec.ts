import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { waitForTinyMce } from '../helpers/editor';

test('tinymce image upload endpoint is configured', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/write');
  await waitForTinyMce(page);

  const uploadUrl = await page.locator('#post-form').getAttribute('data-media-upload-url');
  expect(uploadUrl).toContain('/media/upload');

  const csrfToken = await page.locator('meta[name="csrf-token"]').getAttribute('content');

  const response = await page.request.post(uploadUrl!, {
    headers: { 'X-CSRF-TOKEN': csrfToken! },
    multipart: {
      file: {
        name: 'inline.png',
        mimeType: 'image/png',
        buffer: Buffer.from(
          'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
          'base64',
        ),
      },
    },
  });

  expect(response.ok()).toBeTruthy();
  const json = await response.json();
  expect(json.location).toMatch(/^(\/|https?:\/\/)/);
  expect(json.location).toContain('/storage/media/editor/');
});
