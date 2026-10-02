import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { seedPosts } from '../helpers/seed';

test('author can create a list from post page and auto-add the story', async ({ page }) => {
  const listName = `Modal list ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto(seedPosts.laravel.path);

  await page.locator('#bookmark-btn').click();
  await page.locator('#create-list-btn').click();

  await expect(page.locator('#create-list-modal-title')).toHaveText('Create new list');
  await page.locator('#create-list-name').fill(listName);
  await page.locator('#create-list-submit').click();

  await expect(page.locator('#bookmark-label')).toHaveText('Saved');
  await expect(page.locator('#list-picker-items')).toContainText(listName);

  await page.goto('/me/lists');
  await page.locator('main a.rounded-lg').filter({ hasText: listName }).click();
  await expect(page.locator('main')).toContainText(seedPosts.laravel.title);
});
