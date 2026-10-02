import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { tinyPngBuffer } from '../helpers/fixtures';

test.describe.configure({ timeout: 90_000 });

test('gallery composer accepts photos with caption fields', async ({ page }) => {
  const captionOne = 'Sunset over the river';
  const captionTwo = 'Bridge at dusk';
  const png = tinyPngBuffer();

  await loginAsAuthor(page);
  await page.goto('/write/gallery');

  await expect(page.getByRole('heading', { name: /create gallery/i })).toBeVisible();

  const [fileChooser] = await Promise.all([
    page.waitForEvent('filechooser'),
    page.locator('#gallery-dropzone').click(),
  ]);
  await fileChooser.setFiles([
    { name: 'one.png', mimeType: 'image/png', buffer: png },
    { name: 'two.png', mimeType: 'image/png', buffer: png },
  ]);

  await expect(page.locator('.gallery-photo-card--pending')).toHaveCount(2, { timeout: 15_000 });

  const captions = page.locator('.gallery-photo-card__caption');
  await captions.nth(0).fill(captionOne);
  await captions.nth(1).fill(captionTwo);

  await expect(page.locator('.gallery-photo-card__cover')).toBeVisible();
  await expect(page.locator('.gallery-photo-card__thumb').first()).toHaveAttribute('alt', captionOne);

  const fileCount = await page.locator('#gallery-images-input').evaluate(
    (el) => (el as HTMLInputElement).files?.length ?? 0,
  );
  expect(fileCount).toBe(2);
});

test('gallery compose page links to article and video flows', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/write/gallery');

  await expect(page.getByRole('link', { name: /write.*article|article instead/i })).toBeVisible();
  await expect(page.getByRole('link', { name: /video post|create a video/i })).toBeVisible();
});
