import { test, expect } from '@playwright/test';
import { login, loginAsAdmin, loginAsAuthor } from '../helpers/auth';
import { expectStatusAlert } from '../helpers/messages';

test('user can follow magazine and request to join', async ({ page }) => {
  const magazineName = `E2E Join Mag ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto('/magazines/create');
  await page.locator('input[name="name"]').fill(magazineName);
  await page.locator('textarea[name="description"]').fill('Magazine for join e2e.');
  await page.getByRole('button', { name: 'Create magazine' }).click();
  await expect(page.getByRole('heading', { name: magazineName })).toBeVisible();

  await page.getByRole('button', { name: 'Logout' }).click();

  await loginAsAdmin(page);
  await page.goto('/magazines');
  await page.getByRole('link', { name: magazineName }).click();

  const followBtn = page.locator('#follow-magazine-btn');
  await followBtn.click();
  await expect(followBtn).toHaveText('Following');

  await page.getByText('Request to join').click();
  await page.locator('textarea[name="message"]').fill('I want to write for this magazine.');
  await page.getByRole('button', { name: 'Send request' }).click();
  await expect(page.getByText('Join request pending')).toBeVisible();

  await page.getByRole('button', { name: 'Logout' }).click();

  await login(page, 'author@magazines.test');
  await page.goto('/magazines');
  await page.getByRole('link', { name: magazineName }).click();
  await page.getByRole('link', { name: 'Manage magazine' }).click();

  await expect(page.getByRole('heading', { name: 'Join requests' })).toBeVisible();
  await expect(page.getByText('Super Admin')).toBeVisible();
  await page.getByRole('button', { name: 'Approve' }).first().click();
  await expectStatusAlert(page, 'Join request approved.');
});

test('author can follow and unfollow a magazine', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/magazines/create');
  const magazineName = `E2E Follow Mag ${Date.now()}`;
  await page.locator('input[name="name"]').fill(magazineName);
  await page.getByRole('button', { name: 'Create magazine' }).click();

  const followBtn = page.locator('#follow-magazine-btn');
  await followBtn.click();
  await expect(followBtn).toHaveText('Following');

  await followBtn.click();
  await expect(followBtn).toHaveText('Follow');
});
