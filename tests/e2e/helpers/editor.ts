import { expect, Page } from '@playwright/test';

export async function waitForTinyMce(page: Page) {
  await expect(page.locator('.tox-tinymce')).toBeVisible({ timeout: 15000 });
}

export async function fillTinyMceBody(page: Page, content: string) {
  const body = page.frameLocator('.tox-edit-area iframe').locator('body');
  await body.click();
  await body.fill(content);
  await page.locator('input[name="title"]').click();
}

export async function waitForAutosave(page: Page) {
  await expect(page.locator('#autosave-status')).toContainText(/Saved/i, { timeout: 15000 });
}
