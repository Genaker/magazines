import type { Locator, Page } from '@playwright/test';

/** Visible comment body (excludes hidden edit textarea). */
export function visibleCommentBody(page: Page, text: string): Locator {
  return page.locator('#comments .text-gray-800').filter({ hasText: text });
}

/** Comment article containing the given text. */
export function commentArticle(page: Page, text: string): Locator {
  return page.locator('#comments article').filter({ hasText: text });
}
