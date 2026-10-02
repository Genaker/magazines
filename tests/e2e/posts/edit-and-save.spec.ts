import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { fillTinyMceBody, waitForTinyMce } from '../helpers/editor';

test('author can edit a draft and save changes', async ({ page }) => {
  const originalTitle = `E2E Draft ${Date.now()}`;
  const updatedTitle = `${originalTitle} Updated`;

  await loginAsAuthor(page);
  await page.goto('/write');
  await waitForTinyMce(page);

  await page.locator('input[name="title"]').fill(originalTitle);
  await fillTinyMceBody(page, 'Initial draft body.');
  await page.locator('select[name="status"]').selectOption('draft');
  await page.getByRole('button', { name: 'Publish' }).click();

  await expect(page.getByRole('heading', { name: originalTitle })).toBeVisible();

  await page.getByRole('link', { name: 'Edit' }).click();
  await waitForTinyMce(page);

  await page.locator('input[name="title"]').fill(updatedTitle);
  await fillTinyMceBody(page, 'Updated draft body after edit.');
  await page.locator('select[name="status"]').selectOption('published');

  await page.getByRole('button', { name: 'Save' }).click();

  await expect(page.getByRole('heading', { name: updatedTitle })).toBeVisible({ timeout: 15000 });
  await expect(page.locator('.prose-editorial')).toContainText('Updated draft body after edit.');
});
