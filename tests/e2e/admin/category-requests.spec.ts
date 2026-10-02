import { test, expect } from '@playwright/test';
import { login, loginAsAdmin, loginAsAuthor, navLogoutButton, logout } from '../helpers/auth';

test.describe.configure({ timeout: 60_000 });

test('admin can approve a category request', async ({ page }) => {
  const categoryName = `E2E Approved ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto('/category/request');
  await page.locator('input[name="name"]').fill(categoryName);
  await page.locator('textarea[name="reason"]').fill('Needed for e2e admin approval test.');
  await page.getByRole('button', { name: 'Submit request' }).click();
  await expect(page.locator('main')).toContainText('Status: pending');

  await navLogoutButton(page).click();
  await loginAsAdmin(page);
  await page.goto('/admin/category-requests');

  await expect(page.locator('main')).toContainText(categoryName);
  await page.getByRole('row', { name: categoryName }).getByRole('button', { name: 'Approve' }).click();

  await logout(page);
  await login(page, 'author@magazines.test');
  await page.goto('/me/category-requests');

  await expect(page.locator('main')).toContainText(categoryName);
  await expect(page.locator('main')).toContainText('Status: approved');
});

test('admin can reject a category request with a note', async ({ page }) => {
  const categoryName = `E2E Rejected ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto('/category/request');
  await page.locator('input[name="name"]').fill(categoryName);
  await page.locator('textarea[name="reason"]').fill('Needed for e2e admin rejection test.');
  await page.getByRole('button', { name: 'Submit request' }).click();

  await navLogoutButton(page).click();
  await loginAsAdmin(page);
  await page.goto('/admin/category-requests');

  const row = page.getByRole('row', { name: categoryName });
  await row.locator('input[name="admin_note"]').fill('Too narrow for the site.');
  await row.getByRole('button', { name: 'Reject' }).click();

  await logout(page);
  await login(page, 'author@magazines.test');
  await page.goto('/me/category-requests');

  await expect(page.locator('main')).toContainText(categoryName);
  await expect(page.locator('main')).toContainText('Status: rejected');
  await expect(page.locator('main')).toContainText('Too narrow for the site.');
});
