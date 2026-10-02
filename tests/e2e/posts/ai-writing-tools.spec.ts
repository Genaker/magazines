import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { waitForTinyMce } from '../helpers/editor';

test('write page shows AI assistant controls', async ({ page }) => {
  await loginAsAuthor(page);
  await page.goto('/write');
  await waitForTinyMce(page);

  await expect(page.getByText('AI writing & grammar assistants')).toBeVisible();
  await expect(page.getByLabel('Task')).toBeVisible();
  await expect(page.getByLabel('AI service')).toBeVisible();
  await expect(page.getByLabel(/Your instructions/)).toBeVisible();
  const copyAndOpen = page.getByRole('button', { name: 'Copy & open' });
  await expect(copyAndOpen).toBeVisible();
  await expect(copyAndOpen).toHaveAttribute('title', 'Paste with Cmd+V / Ctrl+V into the chat');
  await expect(page.getByRole('button', { name: 'Copy prompt' })).toBeVisible();

  await page.getByLabel('Task').selectOption('grammar');
  await page.getByLabel('AI service').selectOption('claude');
  await page.getByLabel(/Your instructions/).fill('Use British English spelling.');
});
