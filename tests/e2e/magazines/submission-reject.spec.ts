import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { expectStatusAlert } from '../helpers/messages';
import { fillTinyMceBody, waitForTinyMce } from '../helpers/editor';

test('magazine owner can reject a story submission', async ({ page }) => {
  const magazineName = `E2E Reject Mag ${Date.now()}`;
  const postTitle = `E2E Reject Story ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto('/magazines/create');
  await page.locator('input[name="name"]').fill(magazineName);
  await page.getByRole('button', { name: 'Create magazine' }).click();

  await page.goto('/write');
  await waitForTinyMce(page);
  await page.locator('input[name="title"]').fill(postTitle);
  await fillTinyMceBody(page, 'Story to be rejected.');
  await page.locator('select[name="status"]').selectOption('draft');
  await page.getByRole('button', { name: 'Publish' }).click();

  await page.getByRole('link', { name: 'Edit' }).click();
  await waitForTinyMce(page);
  await page.getByRole('button', { name: new RegExp(`Submit to ${magazineName}`) }).click();
  await expect(page.getByText(`Submitted to ${magazineName} for editorial review.`)).toBeVisible();

  await page.goto('/magazines');
  await page.getByRole('link', { name: magazineName }).click();
  await page.getByRole('link', { name: 'Manage magazine' }).click();

  await expect(page.getByText(postTitle)).toBeVisible();
  await page.getByRole('button', { name: 'Reject' }).first().click();
  await expectStatusAlert(page, 'Submission rejected.');
});
