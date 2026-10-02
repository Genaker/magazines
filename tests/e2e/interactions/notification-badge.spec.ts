import { test, expect } from '@playwright/test';
import { loginAsAdmin, loginAsAuthor, navLogoutButton } from '../helpers/auth';
import { profileSidebar } from '../helpers/profile-sidebar';

test('unread notification shows badge on bell icon', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('/@demoauthor');

  const followBtn = profileSidebar(page).locator('#follow-btn');
  if (await followBtn.getAttribute('data-following') === '1') {
    await followBtn.click();
    await expect(followBtn).toHaveText(/\s*Follow\s*/);
  }
  await followBtn.click();

  await navLogoutButton(page).click();
  await loginAsAuthor(page);
  await page.goto('/');

  await expect(page.locator('nav a[title="Notifications"] span')).toBeVisible();
});
