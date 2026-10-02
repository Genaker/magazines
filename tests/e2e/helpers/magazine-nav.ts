import { expect, Page } from '@playwright/test';
import { loginAsAdmin } from './auth';

export type MagazineNavMode = 'manual' | 'auto';

export type MagazineNavOptions = {
  mode: MagazineNavMode;
  limit?: number;
};

export async function ensureAdminOnMagazineNav(page: Page): Promise<void> {
  await page.goto('/admin/magazines');
  if ((await page.getByRole('heading', { name: 'Magazine menu' }).count()) === 0) {
    await loginAsAdmin(page);
    await page.goto('/admin/magazines');
  }

  await expect(page.getByRole('heading', { name: 'Magazine menu' })).toBeVisible();
}

export async function configureMagazineNav(page: Page, options: MagazineNavOptions): Promise<void> {
  await ensureAdminOnMagazineNav(page);

  const modeLabel = options.mode === 'manual'
    ? 'Choose magazines manually'
    : 'Show top magazines automatically';

  await page.getByRole('radio', { name: modeLabel }).check();

  if (options.limit !== undefined) {
    await page.locator('#magazines_nav_limit').fill(String(options.limit));
  }

  await page.locator('form').filter({ has: page.locator('input[name="magazines_nav_mode"]') }).getByRole('button', { name: 'Save' }).click();
  await expect(page.getByText('Magazine menu settings saved.')).toBeVisible();
}

export async function openMagazinesDropdown(page: Page): Promise<void> {
  await page.locator('nav').getByRole('button', { name: 'Magazines' }).click();
}

export function magazineNavLink(page: Page, name: string) {
  return page.locator('nav').getByRole('link', { name, exact: true });
}

export async function pinMagazineInMenu(page: Page, magazineName: string): Promise<void> {
  await ensureAdminOnMagazineNav(page);
  const row = page.locator('tr', { has: page.getByRole('cell', { name: magazineName, exact: true }) });
  await row.getByRole('button', { name: 'Add to menu' }).click();
  await expect(page.getByText('Magazine menu updated.')).toBeVisible();
}

export async function unpinMagazineFromMenu(page: Page, magazineName: string): Promise<void> {
  await ensureAdminOnMagazineNav(page);
  const row = page.locator('tr', { has: page.getByRole('cell', { name: magazineName, exact: true }) });
  await row.getByRole('button', { name: 'Remove from menu' }).click();
  await expect(page.getByText('Magazine menu updated.')).toBeVisible();
}
