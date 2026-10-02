import { test, expect } from '@playwright/test';
import {
  configureMagazineNav,
  ensureAdminOnMagazineNav,
  magazineNavLink,
  openMagazinesDropdown,
} from '../helpers/magazine-nav';

test('admin can pin magazine to site navigation menu', async ({ page }) => {
  await configureMagazineNav(page, { mode: 'manual' });

  await ensureAdminOnMagazineNav(page);
  await expect(page.getByRole('cell', { name: 'The Commons' })).toBeVisible();

  const row = page.locator('tr', { has: page.getByRole('cell', { name: 'The Commons' }) });
  await row.getByRole('button', { name: 'Remove from menu' }).click();
  await expect(page.getByText('Magazine menu updated.')).toBeVisible();

  await page.goto('/');
  await openMagazinesDropdown(page);
  await expect(magazineNavLink(page, 'The Commons')).toHaveCount(0);
  await expect(page.locator('nav').getByRole('link', { name: 'See all magazines' })).toBeVisible();

  await ensureAdminOnMagazineNav(page);
  await row.getByRole('button', { name: 'Add to menu' }).click();
  await expect(page.getByText('Magazine menu updated.')).toBeVisible();

  await page.goto('/');
  await openMagazinesDropdown(page);
  await expect(magazineNavLink(page, 'The Commons')).toBeVisible();
});
