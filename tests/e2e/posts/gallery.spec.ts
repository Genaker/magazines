import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { waitForTinyMce } from '../helpers/editor';
import { tinyPngBuffer } from '../helpers/fixtures';
import { seedPosts } from '../helpers/seed';

test('write page includes gallery toolbar button', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/write');
  await waitForTinyMce(page);

  await expect(page.getByRole('button', { name: 'Gallery' })).toBeVisible();
});

test('author can insert gallery and open lightbox on published post', async ({ page }) => {
  const title = `E2E Gallery ${Date.now()}`;
  const png = tinyPngBuffer();

  await loginAsAuthor(page);
  await page.goto('/write');
  await waitForTinyMce(page);

  const fileChooserPromise = page.waitForEvent('filechooser');
  await page.getByRole('button', { name: 'Gallery' }).click();
  const fileChooser = await fileChooserPromise;
  await fileChooser.setFiles([
    { name: 'g1.png', mimeType: 'image/png', buffer: png },
    { name: 'g2.png', mimeType: 'image/png', buffer: png },
  ]);

  const gallery = page.frameLocator('.tox-edit-area iframe').locator('.post-gallery[data-component="gallery"]');
  await expect(gallery).toBeVisible({ timeout: 20000 });
  await expect(gallery.locator('img')).toHaveCount(2);

  await page.locator('input[name="title"]').fill(title);
  await page.locator('select[name="status"]').selectOption('published');
  await page.getByRole('button', { name: 'Publish' }).click();

  await expect(page.getByRole('heading', { name: title })).toBeVisible();
  await expect(page.locator('.post-gallery[data-component="gallery"]')).toBeVisible();
  await expect(page.locator('.post-gallery img')).toHaveCount(2);
});

test('seeded gallery post opens lightbox', async ({ page }) => {
  await page.goto(seedPosts.photoWalk.path);

  await expect(page.locator('.post-gallery[data-component="gallery"]')).toBeVisible();
  await page.locator('.post-gallery a').first().click();
  await expect(page.locator('.lg-outer')).toBeVisible({ timeout: 15_000 });
});
