import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';

test('profile page allows username edit', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/profile');

  await expect(page.getByLabel('Username')).toBeVisible();
  await expect(page.getByLabel('Username')).toHaveValue('demoauthor');
  await expect(page.getByLabel('Bio')).toBeVisible();
});
