import { test, expect } from '@playwright/test';
import { seedPosts } from '../helpers/seed';

test('nav categories menu opens dropdown with subcategories', async ({ page }) => {
  await page.goto('/');

  const nav = page.getByRole('navigation');
  await nav.getByRole('button', { name: 'Categories' }).click();

  await expect(nav.getByRole('link', { name: 'Technology' })).toBeVisible();
  await expect(nav.getByRole('link', { name: 'Web Development' })).toBeVisible();
  await expect(nav.getByRole('link', { name: 'Mobile' })).toBeVisible();
  await expect(nav.getByRole('link', { name: 'Artificial Intelligence' })).toBeVisible();
  await expect(nav.getByRole('link', { name: 'Personal Essays' })).toBeVisible();

  await nav.getByRole('link', { name: 'Web Development' }).click();
  await expect(page.getByRole('heading', { name: 'Web Development', level: 1 })).toBeVisible();
});

test('parent category page lists subcategories and nested posts', async ({ page }) => {
  await page.goto('/category/technology');

  const main = page.getByRole('main');

  await expect(main.getByRole('heading', { name: 'Technology', level: 1 })).toBeVisible();
  const subcategories = main.getByRole('heading', { name: 'Subcategories' }).locator('xpath=following-sibling::div[1]');
  await expect(subcategories.getByRole('link', { name: 'Web Development' })).toBeVisible();
  await expect(subcategories.getByRole('link', { name: 'Mobile' })).toBeVisible();
  await expect(main.getByRole('link', { name: seedPosts.laravel.title }).first()).toBeVisible();
});

test('subcategory page shows breadcrumb and post', async ({ page }) => {
  await page.goto('/category/web-development');

  const main = page.getByRole('main');

  await expect(main.getByRole('link', { name: 'Technology' })).toBeVisible();
  await expect(main.getByRole('heading', { name: 'Web Development', level: 1 })).toBeVisible();
  await expect(main.getByRole('link', { name: seedPosts.laravel.title }).first()).toBeVisible();
});

test('writing category still lists its posts', async ({ page }) => {
  await page.goto('/category/writing');

  const main = page.getByRole('main');

  await expect(main.getByRole('heading', { name: 'Writing', level: 1 })).toBeVisible();
  await expect(main.getByRole('navigation', { name: 'Subcategories' }).getByRole('link', { name: 'Personal Essays' })).toBeVisible();
  await expect(main.getByRole('link', { name: seedPosts.whyWriting.title }).first()).toBeVisible();
});
