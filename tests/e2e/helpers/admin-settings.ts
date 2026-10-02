import { expect, Page } from '@playwright/test';
import { loginAsAdmin } from './auth';

export type HomeLayoutOption = 'discover' | 'latest' | 'trending';
export type LocaleCode = 'en' | 'ua';

export type SiteSettingsOptions = {
  homeLayout?: HomeLayoutOption;
  localesEnabled?: LocaleCode[];
  localeDefault?: LocaleCode;
};

const allLocales: LocaleCode[] = ['en', 'ua'];

async function canAccessAdminSettings(page: Page): Promise<boolean> {
  await page.goto('/admin/settings');

  return (await page.locator('#home_layout').count()) > 0;
}

async function ensureAdminOnSettings(page: Page) {
  if (! await canAccessAdminSettings(page)) {
    await loginAsAdmin(page);
    await page.goto('/admin/settings');
  }

  await expect(page.locator('#home_layout')).toBeVisible();
}

async function applySiteSettings(page: Page, options: SiteSettingsOptions) {
  if (options.homeLayout) {
    await page.locator('#home_layout').selectOption(options.homeLayout);
  }

  if (options.localesEnabled) {
    for (const code of allLocales) {
      const checkbox = page.locator(`input[name="locales_enabled[]"][value="${code}"]`);
      if (options.localesEnabled.includes(code)) {
        await checkbox.check();
      } else {
        await checkbox.uncheck();
      }
    }
  }

  if (options.localeDefault) {
    await page.locator('#locale_default').selectOption(options.localeDefault);
  }
}

export async function saveAdminSettings(page: Page, options: SiteSettingsOptions = {}) {
  await applySiteSettings(page, options);
  await page.getByRole('button', { name: 'Save' }).click();
  await expect(page.getByText('Settings saved.')).toBeVisible();
}

export async function configureSiteSettings(page: Page, options: SiteSettingsOptions = {}) {
  await ensureAdminOnSettings(page);
  await saveAdminSettings(page, options);
}

export async function resetSiteSettings(page: Page) {
  await ensureAdminOnSettings(page);
  await saveAdminSettings(page, {
    homeLayout: 'discover',
    localesEnabled: ['en', 'ua'],
    localeDefault: 'en',
  });
}
