import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../helpers/auth';

test('super admin can access admin dashboard and links', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('/admin');

  await expect(page.getByRole('heading', { name: 'Admin dashboard' })).toBeVisible();
  await expect(page.getByRole('navigation', { name: 'Admin' }).getByRole('link', { name: 'Users' })).toBeVisible();
  await expect(page.getByRole('navigation', { name: 'Admin' }).getByRole('link', { name: 'Settings' })).toBeVisible();
  await expect(page.getByRole('main').getByRole('link', { name: 'Users' })).toBeVisible();
  await expect(page.getByRole('main').getByRole('link', { name: 'Posts' })).toBeVisible();
  await expect(page.getByRole('main').getByRole('link', { name: 'Categories' })).toBeVisible();
  await expect(page.getByRole('main').getByRole('link', { name: 'Settings' })).toBeVisible();
  await expect(page.getByRole('main').getByRole('link', { name: 'Trash' }).first()).toBeVisible();
});
