import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import {
  configureMagazineNav,
  ensureAdminOnMagazineNav,
  magazineNavLink,
  openMagazinesDropdown,
  pinMagazineInMenu,
} from '../helpers/magazine-nav';

test('selecting manual mode shows drag table without saving settings', async ({ page }) => {
  await configureMagazineNav(page, { mode: 'auto' });

  await ensureAdminOnMagazineNav(page);
  await expect(page.locator('#magazine-nav-manual-panel')).toBeHidden();

  await page.getByRole('radio', { name: 'Choose magazines manually' }).check();
  await expect(page.locator('#magazine-nav-manual-panel')).toBeVisible();
  await expect(page.locator('#magazine-nav-manual-panel').getByRole('table')).toBeVisible();
});

test('auto mode hides manual table and uses weekly activity ranking', async ({ page }) => {
  await configureMagazineNav(page, { mode: 'auto', limit: 5 });

  await ensureAdminOnMagazineNav(page);
  await expect(page.locator('#magazine-nav-manual-panel')).toBeHidden();

  await page.goto('/');
  await openMagazinesDropdown(page);
  await expect(magazineNavLink(page, 'The Commons')).toBeVisible();
});

test('manual mode shows only pinned magazines in public navigation', async ({ page }) => {
  await configureMagazineNav(page, { mode: 'manual' });

  await unpinCommonsIfPinned(page);

  await page.goto('/');
  await openMagazinesDropdown(page);
  await expect(magazineNavLink(page, 'The Commons')).toHaveCount(0);

  await pinMagazineInMenu(page, 'The Commons');

  await page.goto('/');
  await openMagazinesDropdown(page);
  await expect(magazineNavLink(page, 'The Commons')).toBeVisible();
});

test('admin can reorder pinned magazines via drag and drop', async ({ page }) => {
  const suffix = Date.now();
  const firstName = `E2E Nav First ${suffix}`;
  const secondName = `E2E Nav Second ${suffix}`;

  await configureMagazineNav(page, { mode: 'manual' });

  await loginAsAuthor(page);
  for (const name of [firstName, secondName]) {
    await page.goto('/magazines/create');
    await page.locator('input[name="name"]').fill(name);
    await page.locator('textarea[name="description"]').fill('Magazine nav reorder e2e.');
    await page.getByRole('button', { name: 'Create magazine' }).click();
    await expect(page.getByRole('heading', { name })).toBeVisible();
  }

  await pinMagazineInMenu(page, firstName);
  await pinMagazineInMenu(page, secondName);

  await ensureAdminOnMagazineNav(page);

  const firstRow = page.locator('tr[data-magazine-id]', { has: page.getByRole('cell', { name: firstName }) });
  const secondRow = page.locator('tr[data-magazine-id]', { has: page.getByRole('cell', { name: secondName }) });

  await secondRow.locator('[data-drag-handle]').dragTo(firstRow.locator('[data-drag-handle]'));

  await expect(page.getByText('Magazine menu order saved.')).toBeVisible();

  await page.goto('/');
  await openMagazinesDropdown(page);

  const links = page.locator('nav a', { hasText: new RegExp(`${firstName}|${secondName}`) });
  await expect(links.nth(0)).toHaveText(secondName);
  await expect(links.nth(1)).toHaveText(firstName);
});

async function unpinCommonsIfPinned(page: import('@playwright/test').Page): Promise<void> {
  await ensureAdminOnMagazineNav(page);
  await page.getByRole('radio', { name: 'Choose magazines manually' }).check();

  const row = page.locator('tr', { has: page.getByRole('cell', { name: 'The Commons', exact: true }) });
  const removeButton = row.getByRole('button', { name: 'Remove from menu' });

  if ((await removeButton.count()) > 0) {
    await removeButton.click();
    await expect(page.getByText('Magazine menu updated.')).toBeVisible();
  }
}
