import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { fillTinyMceBody, waitForTinyMce } from '../helpers/editor';

test('author can create and publish a new story', async ({ page }) => {
  const title = `E2E Published ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto('/write');
  await waitForTinyMce(page);

  await page.locator('input[name="title"]').fill(title);
  await page.locator('input[name="subtitle"]').fill('E2E subtitle');
  await fillTinyMceBody(page, 'Published body content for the e2e test.');
  await page.locator('input[name="tags"]').fill('e2e, testing');
  await page.locator('select[name="status"]').selectOption('published');

  await page.getByRole('button', { name: 'Publish' }).click();

  await expect(page.getByRole('heading', { name: title })).toBeVisible();
  await expect(page.locator('.prose-editorial')).toContainText('Published body content');
  await expect(page.getByRole('link', { name: '#e2e' })).toBeVisible();

  const postUrl = page.url();
  await page.reload();
  await expect(page.getByRole('heading', { name: title })).toBeVisible();
  expect(postUrl).toContain('/@demoauthor/');
});
