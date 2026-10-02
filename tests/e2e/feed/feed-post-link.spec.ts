import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { fillTinyMceBody, waitForTinyMce } from '../helpers/editor';

test('home feed article link opens published story', async ({ page }) => {
  const title = `E2E Published ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto('/write');
  await waitForTinyMce(page);

  await page.locator('input[name="title"]').fill(title);
  await fillTinyMceBody(page, 'Feed link body content for readers.');
  await page.locator('select[name="status"]').selectOption('published');
  await page.getByRole('button', { name: 'Publish' }).click();

  await expect(page.getByRole('heading', { name: title })).toBeVisible();

  await page.goto('/');

  const feedArticleLink = page.locator('article.border-b h2 a.hover\\:underline', { hasText: title }).first();
  await expect(feedArticleLink).toBeVisible();

  const href = await feedArticleLink.getAttribute('href');
  expect(href).toMatch(/\/@demoauthor\/e2e-published-\d+$/);

  const response = await page.request.get(href!);
  expect(response.status()).toBe(200);
  await expect(response.text()).resolves.toContain(title);

  await feedArticleLink.click();

  await expect(page.locator('article h1')).toHaveText(title);
  await expect(page.locator('.prose-editorial')).toContainText('Feed link body content');
});
