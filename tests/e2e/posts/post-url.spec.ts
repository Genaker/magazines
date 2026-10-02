import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { fillTinyMceBody, waitForTinyMce } from '../helpers/editor';
import { seedPosts } from '../helpers/seed';

test('seeded published posts are visible by url', async ({ page }) => {
  for (const path of [seedPosts.whyWriting.path, seedPosts.laravel.path]) {
    await page.goto(path);
    await expect(page.locator('article h1')).toBeVisible();
  }
});

test('published post url remains visible after reload', async ({ page }) => {
  const title = `E2E Published ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto('/write');
  await waitForTinyMce(page);

  await page.locator('input[name="title"]').fill(title);
  await fillTinyMceBody(page, 'Published body content for the e2e test.');
  await page.locator('select[name="status"]').selectOption('published');
  await page.getByRole('button', { name: 'Publish' }).click();

  await expect(page.getByRole('heading', { name: title })).toBeVisible();

  const postUrl = page.url();
  expect(postUrl).toMatch(/\/@demoauthor\/e2e-published-\d+$/);

  await page.reload();
  await expect(page.getByRole('heading', { name: title })).toBeVisible();
  await expect(page.locator('.prose-editorial')).toContainText('Published body content');
});
