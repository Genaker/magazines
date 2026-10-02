import { Locator, Page } from '@playwright/test';

export function localeSwitcherLinks(page: Page | Locator): Locator {
  return page.locator('nav a[href*="/locale/"]');
}

export function localeLink(page: Page | Locator, code: 'en' | 'ua'): Locator {
  return page.locator(`nav a[href$="/locale/${code}"]`);
}
