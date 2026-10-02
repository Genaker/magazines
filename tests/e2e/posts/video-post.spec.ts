import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { fillTinyMceBody, waitForTinyMce } from '../helpers/editor';

test.describe.configure({ timeout: 90_000 });

test('author can publish video post with embed above description', async ({ page }) => {
  const title = `E2E Video Post ${Date.now()}`;
  const body = 'Notes and context below the player.';

  await loginAsAuthor(page);
  await page.goto('/write/video');

  await expect(page.getByRole('heading', { name: /create video/i })).toBeVisible();

  await page.locator('input[name="title"]').fill(title);
  await page.locator('select[name="category_id"]').selectOption({ label: '— Web Development' });
  await page.locator('#video_url').fill('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
  await waitForTinyMce(page);
  await fillTinyMceBody(page, body);
  await page.locator('select[name="status"]').selectOption('published');
  await page.getByRole('button', { name: /publish video/i }).click();
  await expect(page.getByRole('heading', { name: title })).toBeVisible({ timeout: 30_000 });

  await expect(page.getByRole('heading', { name: title })).toBeVisible();
  await expect(page.locator('.post-video-embed')).toBeVisible();
  await expect(page.locator('.post-video-embed iframe')).toHaveAttribute('src', /youtube\.com\/embed\/dQw4w9WgXcQ/);
  await expect(page.locator('.prose-editorial')).toContainText(body);

  const embedBox = await page.locator('.post-video-embed').boundingBox();
  const proseBox = await page.locator('.prose-editorial').boundingBox();
  expect(embedBox).not.toBeNull();
  expect(proseBox).not.toBeNull();
  expect(embedBox!.y).toBeLessThan(proseBox!.y);
});

test('video compose page links to article and gallery flows', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/write/video');

  await expect(page.getByRole('link', { name: /write.*article|article instead/i })).toBeVisible();
  await expect(page.getByRole('link', { name: /gallery|photo gallery/i })).toBeVisible();
});
