import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { profileSidebar } from '../helpers/profile-sidebar';

test('own profile shows sidebar actions and upload link', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/@demoauthor');

  const sidebar = profileSidebar(page);
  await expect(sidebar.getByRole('link', { name: 'Edit profile' })).toBeVisible();
  await expect(sidebar.getByRole('link', { name: 'My stories' })).toBeVisible();
  await expect(sidebar.getByRole('link', { name: 'Stats' })).toBeVisible();
  await expect(sidebar.getByText('Upload avatar image')).toBeVisible();
  await expect(sidebar.locator('#follow-btn')).toBeVisible();
});

test('author can follow and unfollow themselves on own profile', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/@demoauthor');

  const followBtn = profileSidebar(page).locator('#follow-btn');
  await expect(followBtn).toHaveCSS('cursor', 'pointer');

  await followBtn.click();
  await expect(followBtn).toHaveText(/\s*Following\s*/);
  await expect(followBtn).toHaveClass(/bg-gray-900/);

  await followBtn.click();
  await expect(followBtn).toHaveText(/\s*Follow\s*/);
});
