import { test, expect } from '@playwright/test';
import { loginAsAdmin, loginAsAuthor, navLogoutButton } from '../helpers/auth';
import { profileSidebar } from '../helpers/profile-sidebar';

test('follow creates a notification for the followed user', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('/@demoauthor');

  const followBtn = profileSidebar(page).locator('#follow-btn');
  if (await followBtn.getAttribute('data-following') === '1') {
    await followBtn.click();
    await expect(followBtn).toHaveText(/\s*Follow\s*/);
  }
  await followBtn.click();
  await expect(followBtn).toHaveText(/\s*Following\s*/);

  await navLogoutButton(page).click();

  await loginAsAuthor(page);
  await page.getByRole('link', { name: 'Notifications' }).click();

  await expect(page.getByRole('heading', { name: 'Notifications' })).toBeVisible();
  await expect(page.getByText('followed you').first()).toBeVisible();
});

test('user can mark all notifications as read', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('/@demoauthor');
  const followBtn = profileSidebar(page).locator('#follow-btn');
  if (await followBtn.getAttribute('data-following') === '1') {
    await followBtn.click();
  }
  await followBtn.click();

  await navLogoutButton(page).click();
  await loginAsAuthor(page);

  await page.goto('/me/notifications');
  await page.getByRole('button', { name: 'Mark all as read' }).click();

  await expect(page.getByText('All notifications marked as read.')).toBeVisible();
});
