import { expect, Page } from '@playwright/test';
import { tenantSeed, tenantUrl } from './tenant';

export function navWriteLink(page: Page) {
  return page.getByRole('navigation').getByRole('link', { name: 'Write', exact: true });
}

export function navLogoutButton(page: Page) {
  return page.getByRole('navigation').getByRole('button', { name: 'Logout' });
}

async function isLoggedInAs(page: Page, username: string): Promise<boolean> {
  await page.goto('/');

  return (await page.getByRole('navigation').getByRole('link', { name: `@${username}` }).count()) > 0;
}

export async function ensureLoggedOut(page: Page): Promise<void> {
  await page.goto('/');

  if ((await navWriteLink(page).count()) > 0) {
    await navLogoutButton(page).click();
    await expect(navWriteLink(page)).toHaveCount(0);
  }
}

export async function logout(page: Page) {
  await ensureLoggedOut(page);
}

export async function logoutViaPost(page: Page): Promise<void> {
  await page.goto('/login');
  const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');

  await page.request.post('/logout', {
    headers: {
      'X-CSRF-TOKEN': token ?? '',
    },
  });
}

export async function login(page: Page, email: string, password = 'password') {
  await page.goto('/login/password');
  await expect(page.locator('#email')).toBeVisible();
  await page.locator('#email').fill(email);
  await page.locator('#password').fill(password);
  await page.getByRole('button', { name: /log in|увійти/i }).click();
  await expect(page).not.toHaveURL(/\/login/, { timeout: 15_000 });
}

export async function loginAsAuthor(page: Page) {
  if (await isLoggedInAs(page, 'demoauthor')) {
    return;
  }

  await ensureLoggedOut(page);
  await login(page, 'author@magazines.test');
  await expect(navWriteLink(page)).toBeVisible();
}

export async function loginAsTechWriter(page: Page) {
  if (await isLoggedInAs(page, 'techwriter')) {
    return;
  }

  await ensureLoggedOut(page);
  await login(page, 'tech@magazines.test');
  await expect(navWriteLink(page)).toBeVisible();
}

export async function loginAsAdmin(page: Page, host?: string) {
  const settingsUrl = host ? tenantUrl(host, '/admin/settings') : '/admin/settings';
  const loginUrl = host ? tenantUrl(host, '/admin/login/password') : '/admin/login/password';

  await page.goto(settingsUrl);

  if (await page.locator('#home_layout').count() > 0) {
    return;
  }

  await page.goto(loginUrl);
  await expect(page.locator('#email')).toBeVisible();
  await page.locator('#email').fill('admin@magazines.test');
  await page.locator('#password').fill('password');
  await page.getByRole('button', { name: /log in|увійти/i }).click();
  await expect(page).toHaveURL(/\/admin(?:\/|$)/, { timeout: 15_000 });

  await page.goto(settingsUrl);
  await expect(page.locator('#home_layout')).toBeVisible();
}
