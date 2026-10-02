import { test, expect } from '@playwright/test';
import { login, loginAsAuthor } from '../helpers/auth';
import { visibleCommentBody } from '../helpers/comments';
import { seedPosts } from '../helpers/seed';

test('post author can delete a comment on their story', async ({ page }) => {
  const commentText = `Delete me ${Date.now()}`;

  await login(page, 'admin@magazines.test');
  await page.goto(seedPosts.whyWriting.path);
  await page.locator('#comment-body').fill(commentText);
  await page.getByRole('button', { name: 'Post comment' }).click();
  await expect(visibleCommentBody(page, commentText)).toBeVisible();

  await page.getByRole('button', { name: 'Logout' }).click();
  await loginAsAuthor(page);
  await page.goto(seedPosts.whyWriting.path);

  const comment = page.locator(`article:has-text("${commentText}")`);
  await comment.hover();

  page.once('dialog', (dialog) => dialog.accept());
  await comment.getByRole('button', { name: 'Delete' }).click();

  await expect(visibleCommentBody(page, commentText)).toHaveCount(0);
});
