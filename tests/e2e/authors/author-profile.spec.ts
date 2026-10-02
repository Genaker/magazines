import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../helpers/auth';
import { profileSidebar } from '../helpers/profile-sidebar';
import { seedPosts } from '../helpers/seed';

test('author profile shows stats and published posts', async ({ page }) => {
  await page.goto('/@demoauthor');

  await expect(page.getByRole('heading', { name: 'Demo Author', level: 1 })).toBeVisible();
  await expect(page.getByRole('navigation', { name: 'Author profile' }).getByRole('link', { name: 'Home' })).toBeVisible();
  await expect(page.getByRole('navigation', { name: 'Author profile' }).getByRole('link', { name: 'About' })).toBeVisible();
  await expect(profileSidebar(page).getByText(/\d+ followers?/)).toBeVisible();
  await expect(page.getByRole('link', { name: seedPosts.whyWriting.title }).first()).toBeVisible();
});

test('logged-in user sees follow subscribe and block actions on author profile', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('/@demoauthor');

  const sidebar = profileSidebar(page);
  await expect(sidebar.locator('#follow-btn')).toBeVisible();
  await expect(sidebar.locator('#subscribe-btn')).toBeVisible();
  await expect(sidebar.getByRole('button', { name: 'Block' })).toBeVisible();
});
