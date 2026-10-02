import { test, expect } from '@playwright/test';
import { login, loginAsAdmin, loginAsAuthor, navLogoutButton } from '../helpers/auth';
import { expectStatusAlert } from '../helpers/messages';
import { profileSidebar } from '../helpers/profile-sidebar';
import { seedPosts } from '../helpers/seed';

test('author can block a user and blocked user loses access to their content', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/@superadmin');

  const blockBtn = profileSidebar(page).getByRole('button', { name: /^Block$|^Unblock$/ });
  if ((await blockBtn.textContent())?.trim() === 'Block') {
    await blockBtn.click();
    await expectStatusAlert(page, 'User blocked');
  }

  await expect(profileSidebar(page).getByRole('button', { name: 'Unblock' })).toBeVisible();

  await navLogoutButton(page).click();
  await expect(page.getByRole('navigation').getByRole('link', { name: 'Login', exact: true })).toBeVisible();

  await login(page, 'admin@magazines.test');
  await page.goto('/@demoauthor');

  await expect(page.getByText("This author's posts are not available to you.")).toBeVisible();

  const response = await page.goto(seedPosts.whyWriting.path);
  expect(response?.status()).toBe(403);

  await page.goto('/');
  await navLogoutButton(page).click();
  await loginAsAuthor(page);
  await page.goto('/@superadmin');
  await profileSidebar(page).getByRole('button', { name: 'Unblock' }).click();
  await expectStatusAlert(page, 'User unblocked');
});
