import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { fillTinyMceBody, waitForTinyMce } from '../helpers/editor';

test('author can create magazine, submit draft, and approve publication', async ({ page }) => {
  const magazineName = `E2E Magazine ${Date.now()}`;
  const postTitle = `E2E Magazine Story ${Date.now()}`;

  await loginAsAuthor(page);

  await page.goto('/magazines/create');
  await page.locator('input[name="name"]').fill(magazineName);
  await page.locator('textarea[name="description"]').fill('A magazine for e2e testing.');
  await page.getByRole('button', { name: 'Create magazine' }).click();

  await expect(page.getByRole('heading', { name: magazineName })).toBeVisible();

  await page.goto('/write');
  await waitForTinyMce(page);
  await page.locator('input[name="title"]').fill(postTitle);
  await fillTinyMceBody(page, 'Magazine submission body.');
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
  await page.getByRole('button', { name: 'Approve' }).first().click();

  await page.goto('/magazines');
  await page.getByRole('link', { name: magazineName }).click();

  await expect(page.getByRole('link', { name: postTitle })).toBeVisible();
});

test('magazines index is reachable from navigation', async ({ page }) => {
  await page.goto('/');

  await page.locator('nav').getByRole('button', { name: 'Magazines' }).click();
  await expect(page.locator('nav').getByRole('link', { name: 'The Commons' })).toBeVisible();
  await page.locator('nav').getByRole('link', { name: 'See all magazines' }).click();
  await expect(page.getByRole('heading', { name: 'Magazines' })).toBeVisible();
});

test('magazines index search filters the list', async ({ page }) => {
  await page.goto('/magazines', { waitUntil: 'domcontentloaded' });

  const searchInput = page.locator('main input[name="q"]');
  await expect(searchInput).toBeVisible();
  await searchInput.fill('Commons');
  await searchInput.press('Enter');

  await expect(page).toHaveURL(/\/magazines\?q=Commons/);
  await expect(page.getByRole('main').getByRole('link', { name: 'The Commons' })).toBeVisible();
});
