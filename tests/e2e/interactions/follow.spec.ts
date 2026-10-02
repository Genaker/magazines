import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { profileSidebar } from '../helpers/profile-sidebar';

test('author can follow and unfollow a user', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/@superadmin');

  const followBtn = profileSidebar(page).locator('#follow-btn');
  await expect(followBtn).toHaveCSS('cursor', 'pointer');
  await followBtn.click();
  await expect(followBtn).toHaveText(/\s*Following\s*/);

  await followBtn.click();
  await expect(followBtn).toHaveText(/\s*Follow\s*/);
});

test('author can follow and unfollow a category', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/category/technology');

  const followBtn = page.locator('#follow-category-btn');
  await followBtn.click();
  await expect(followBtn).toHaveText('Following');

  await followBtn.click();
  await expect(followBtn).toHaveText('Follow');
});

test('author can follow and unfollow a magazine', async ({ page }) => {
  const magazineName = `E2E Nav Mag ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto('/magazines/create');
  await page.locator('input[name="name"]').fill(magazineName);
  await page.getByRole('button', { name: 'Create magazine' }).click();

  const followBtn = page.locator('#follow-magazine-btn');
  await followBtn.click();
  await expect(followBtn).toHaveText('Following');

  await followBtn.click();
  await expect(followBtn).toHaveText('Follow');
});

test('author can follow and unfollow a tag', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/tag/laravel');

  const followBtn = page.locator('#follow-tag-btn');
  await followBtn.click();
  await expect(followBtn).toHaveText('Following');

  await followBtn.click();
  await expect(followBtn).toHaveText('Follow');
});
