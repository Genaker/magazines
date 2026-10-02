import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { expectStatusAlert } from '../helpers/messages';
import { profileSidebar } from '../helpers/profile-sidebar';

test('author can unblock a user from profile settings', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/@superadmin');

  const blockBtn = profileSidebar(page).getByRole('button', { name: /^Block$|^Unblock$/ });
  if ((await blockBtn.textContent())?.trim() === 'Unblock') {
    await blockBtn.click();
    await expectStatusAlert(page, 'User unblocked');
  }
  await blockBtn.click();
  await expectStatusAlert(page, 'User blocked');

  await page.goto('/profile');
  await expect(page.getByRole('heading', { name: 'Blocked users' })).toBeVisible();
  await expect(page.locator('main')).toContainText('Super Admin');

  await page.getByRole('button', { name: 'Unblock' }).click();
  await expect(page.locator('main')).toContainText('User unblocked.');
  await expect(page.getByText('You have not blocked anyone')).toBeVisible();
});
