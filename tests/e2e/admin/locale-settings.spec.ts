import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../helpers/auth';
import { configureSiteSettings, resetSiteSettings } from '../helpers/admin-settings';
import { localeLink, localeSwitcherLinks } from '../helpers/locale';

test.describe.configure({ timeout: 60_000 });

test.describe('language settings', () => {
  test.afterEach(async ({ page }) => {
    await resetSiteSettings(page);
  });

  test('admin settings page shows language controls', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('/admin/settings');

  await expect(page.getByRole('heading', { name: 'Language' })).toBeVisible();
  await expect(page.locator('input[name="locales_enabled[]"][value="en"]')).toBeVisible();
  await expect(page.locator('input[name="locales_enabled[]"][value="ua"]')).toBeVisible();
  await expect(page.locator('#locale_default')).toBeVisible();
});

test('single enabled language hides locale switcher', async ({ page }) => {
  await configureSiteSettings(page, {
    localesEnabled: ['en'],
    localeDefault: 'en',
  });

  await page.goto('/');

  await expect(localeSwitcherLinks(page)).toHaveCount(0);
  await expect(page.getByRole('heading', { name: 'Home' })).toBeVisible();
});

test('multiple enabled languages show switcher and allow switching', async ({ page }) => {
  await configureSiteSettings(page, {
    localesEnabled: ['en', 'ua'],
    localeDefault: 'en',
  });

  await page.goto('/');

  await expect(localeLink(page, 'en')).toBeVisible();
  await expect(localeLink(page, 'ua')).toBeVisible();

  await localeLink(page, 'ua').click();

  await expect(page.getByRole('heading', { name: 'Головна' })).toBeVisible();
  });
});

test('ukrainian default applies to new visitors', async ({ browser }) => {
  const adminContext = await browser.newContext();
  const adminPage = await adminContext.newPage();

  await configureSiteSettings(adminPage, {
    localesEnabled: ['en', 'ua'],
    localeDefault: 'ua',
  });
  await adminContext.close();

  const guestContext = await browser.newContext();
  const guestPage = await guestContext.newPage();
  await guestPage.goto('/');

  await expect(guestPage.getByRole('heading', { name: 'Головна' })).toBeVisible();
  await expect(localeLink(guestPage, 'en')).toBeVisible();
  await expect(localeLink(guestPage, 'ua')).toBeVisible();

  await guestContext.close();

  const restoreContext = await browser.newContext();
  const restorePage = await restoreContext.newPage();
  await resetSiteSettings(restorePage);
  await restoreContext.close();
});
