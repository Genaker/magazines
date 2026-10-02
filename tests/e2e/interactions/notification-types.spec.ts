import { test, expect } from '@playwright/test';
import { loginAsAdmin, loginAsAuthor, loginAsTechWriter, navLogoutButton } from '../helpers/auth';
import { visibleCommentBody } from '../helpers/comments';
import { seedPosts } from '../helpers/seed';

test('like creates a notification for the post author', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto(seedPosts.whyWriting.path);

  const likeBtn = page.locator('#like-btn');
  const likeCount = page.locator('#like-count');
  const initialCount = Number(await likeCount.textContent());

  if (initialCount > 0) {
    await likeBtn.click();
    await expect.poll(async () => Number(await likeCount.textContent())).toBe(initialCount - 1);
  }
  await likeBtn.click();

  await navLogoutButton(page).click();
  await loginAsAuthor(page);
  await page.goto('/me/notifications');

  await expect(page.getByText('clapped for')).toBeVisible();
});

test('comment creates a notification for the post author', async ({ page }) => {
  const commentText = `Notify comment ${Date.now()}`;

  await loginAsAdmin(page);
  await page.goto(seedPosts.laravel.path);
  await page.locator('#comment-body').fill(commentText);
  await page.getByRole('button', { name: 'Post comment' }).click();

  await navLogoutButton(page).click();
  await loginAsTechWriter(page);
  await page.goto('/me/notifications');

  await expect(page.getByText('commented on').first()).toBeVisible();
});

test('comment like creates a notification for the comment author', async ({ page }) => {
  const commentText = `Notify like ${Date.now()}`;

  await loginAsAdmin(page);
  await page.goto(seedPosts.whyWriting.path);
  await page.locator('#comment-body').fill(commentText);
  await page.getByRole('button', { name: 'Post comment' }).click();
  await expect(visibleCommentBody(page, commentText)).toBeVisible();

  await navLogoutButton(page).click();
  await loginAsAuthor(page);
  await page.goto(seedPosts.whyWriting.path);

  const comment = page.locator('#comments article').filter({ hasText: commentText });
  await comment.locator('.comment-like-btn').click();
  await expect(comment.locator('.comment-like-count')).toHaveText('1');

  await navLogoutButton(page).click();
  await loginAsAdmin(page);
  await page.goto('/me/notifications');

  await expect(page.getByText('liked your comment on').first()).toBeVisible();
});

test('mention creates a notification for the mentioned user', async ({ page }) => {
  const mentionText = `@superadmin mention notify ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto(seedPosts.whyWriting.path);
  await page.locator('#comment-body').fill(mentionText);
  await page.getByRole('button', { name: 'Post comment' }).click();

  await navLogoutButton(page).click();
  await loginAsAdmin(page);
  await page.goto('/me/notifications');

  await expect(page.getByText('mentioned you in a comment on').first()).toBeVisible();
});
