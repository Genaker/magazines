import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';

test('profile sidebar links to bookmarks, stories, and category request', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/profile');

  const sidebar = () => page.getByRole('navigation', { name: 'Profile menu' });

  await expect(sidebar().getByRole('link', { name: 'Profile' })).toBeVisible();
  await expect(sidebar().getByRole('link', { name: 'My stories' })).toBeVisible();
  await expect(sidebar().getByRole('link', { name: 'Stats' })).toBeVisible();
  await expect(sidebar().getByRole('link', { name: 'Reading lists' })).toBeVisible();
  await expect(sidebar().getByRole('link', { name: 'Notifications' })).toBeVisible();
  await expect(sidebar().getByRole('link', { name: 'Request category' })).toBeVisible();

  await sidebar().getByRole('link', { name: 'My stories' }).click();
  await page.waitForURL(/\/me\/posts$/);
  await expect(page.locator('main h1')).toHaveText('My stories');

  await sidebar().getByRole('link', { name: 'Reading lists' }).click();
  await page.waitForURL(/\/me\/lists$/);
  await expect(page.locator('main h1')).toHaveText('Reading lists');

  await sidebar().getByRole('link', { name: 'Notifications' }).click();
  await page.waitForURL(/\/me\/notifications$/);
  await expect(page.locator('main h1')).toHaveText('Notifications');

  await page.goto('/profile');
  await sidebar().getByRole('link', { name: 'Request category' }).click();
  await page.waitForURL(/\/category\/request$/);
  await expect(page.locator('main h1')).toHaveText('Request a category');
});
