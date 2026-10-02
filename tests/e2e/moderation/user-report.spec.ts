import { test, expect } from '@playwright/test';
import { loginAsAdmin, loginAsAuthor, navLogoutButton } from '../helpers/auth';
import { profileSidebar } from '../helpers/profile-sidebar';

test('author can report a user and admin can dismiss the report', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/@superadmin');

  await profileSidebar(page).getByRole('button', { name: 'Report' }).click();
  await page.locator('#report-modal textarea[name="reason"]').fill('This is an e2e test report with enough detail.');
  await page.getByRole('button', { name: 'Submit report' }).click();

  await expect(page.getByText('Report submitted. Admins will review it.')).toBeVisible();

  await navLogoutButton(page).click();
  await loginAsAdmin(page);
  await page.goto('/admin/user-reports');

  await expect(page.locator('main h1')).toHaveText('User reports');
  await expect(page.locator('main')).toContainText('Super Admin');
  await expect(page.locator('main')).toContainText('e2e test report');

  await page.getByRole('button', { name: 'Dismiss' }).click();
  await expect(page.locator('main')).toContainText('dismissed');
});
