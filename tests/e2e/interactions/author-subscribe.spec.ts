import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../helpers/auth';
import { profileSidebar } from '../helpers/profile-sidebar';

test('user can subscribe to an author from their profile', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('/@demoauthor');

  const subscribeBtn = profileSidebar(page).locator('#subscribe-btn');

  if (await subscribeBtn.getAttribute('data-subscribed') === '1') {
    await subscribeBtn.click();
    await expect(subscribeBtn).toHaveAttribute('data-subscribed', '0');
  }

  await subscribeBtn.click();
  await expect(subscribeBtn).toHaveAttribute('data-subscribed', '1');

  await subscribeBtn.click();
  await expect(subscribeBtn).toHaveAttribute('data-subscribed', '0');

  await subscribeBtn.click();
  await expect(subscribeBtn).toHaveAttribute('data-subscribed', '1');
});
