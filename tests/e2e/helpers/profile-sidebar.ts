import { Page } from '@playwright/test';

/** First profile sidebar in DOM (matches getElementById hooks in post.js). */
export function profileSidebar(page: Page) {
  return page.locator('aside').first();
}
