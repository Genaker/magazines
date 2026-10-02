import { expect, Page } from '@playwright/test';

export function statusAlert(page: Page, text: string | RegExp) {
  return page.getByRole('alert').filter({ hasText: text });
}

export async function expectStatusAlert(page: Page, text: string | RegExp): Promise<void> {
  await expect(statusAlert(page, text)).toBeVisible();
}
