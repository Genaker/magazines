import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { visibleCommentBody } from '../helpers/comments';
import { expectStatusAlert } from '../helpers/messages';
import { seedPosts } from '../helpers/seed';

test('logged-in user can post comment and reply on a story', async ({ page }) => {
  const commentText = `E2E comment ${Date.now()}`;
  const replyText = `E2E reply ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto(seedPosts.whyWriting.path);

  await expect(page.getByRole('heading', { name: /Discussion/ })).toBeVisible();
  await page.locator('#comment-body').fill(commentText);
  await page.getByRole('button', { name: 'Post comment' }).click();

  await expectStatusAlert(page, 'Comment posted.');
  await expect(visibleCommentBody(page, commentText)).toBeVisible();

  const comment = page.locator('#comments > article').filter({ hasText: commentText }).first();
  await page.waitForFunction(() => typeof window.Alpine !== 'undefined');
  await comment.getByRole('button', { name: 'Reply' }).click();
  await expect.poll(async () => {
    return comment.evaluate((article) => {
      const alpineEl = article.querySelector('[x-data]') as HTMLElement & { _x_dataStack?: Array<{ replying: boolean }> };

      return alpineEl?._x_dataStack?.[0]?.replying === true;
    });
  }).toBe(true);

  const replyForm = comment.locator('form').filter({ has: page.locator('input[name="parent_id"]') });
  await replyForm.locator('textarea[name="body"]').fill(replyText);
  await replyForm.evaluate((form: HTMLFormElement) => form.requestSubmit());

  await expect(visibleCommentBody(page, replyText)).toBeVisible();
});

test('guest sees login prompt instead of comment form', async ({ page }) => {
  await page.goto(seedPosts.whyWriting.path);

  await expect(page.getByRole('link', { name: 'Log in' })).toBeVisible();
  await expect(page.locator('#comment-body')).toHaveCount(0);
});

test('user can like a comment', async ({ page }) => {
  const commentText = `E2E like target ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto(seedPosts.whyWriting.path);

  await page.locator('#comment-body').fill(commentText);
  await page.getByRole('button', { name: 'Post comment' }).click();
  await expect(visibleCommentBody(page, commentText)).toBeVisible();

  const comment = page.locator('#comments > article').filter({ hasText: commentText });
  const likeBtn = comment.locator('.comment-like-btn');
  await likeBtn.click({ force: true });

  await expect(likeBtn.locator('.comment-like-count')).toHaveText('1');
});

test('top sort shows most liked root comment first', async ({ page }) => {
  const lowText = `E2E top sort low ${Date.now()}`;
  const highText = `E2E top sort high ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto(seedPosts.whyWriting.path);

  await page.locator('#comment-body').fill(lowText);
  await page.getByRole('button', { name: 'Post comment' }).click();
  await expect(visibleCommentBody(page, lowText)).toBeVisible();

  await page.locator('#comment-body').fill(highText);
  await page.getByRole('button', { name: 'Post comment' }).click();
  await expect(visibleCommentBody(page, highText)).toBeVisible();

  const lowComment = page.locator('#comments > article').filter({ hasText: lowText });
  await lowComment.locator('.comment-like-btn').click({ force: true });
  await expect(lowComment.locator('.comment-like-count')).toHaveText('1');

  await page.getByRole('link', { name: 'Top' }).click();
  await expect(page).toHaveURL(/comments=top/);

  const rootComments = page.locator('#comments > article');
  await expect(rootComments.first()).toContainText(lowText);
});

test('mention renders as profile link', async ({ page }) => {
  const mentionText = `@superadmin ping ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto(seedPosts.whyWriting.path);

  await page.locator('#comment-body').fill(mentionText);
  await page.getByRole('button', { name: 'Post comment' }).click();
  await expectStatusAlert(page, 'Comment posted.');

  await expect(page.locator('#comments a[href$="/@superadmin"]')).toBeVisible();
});

test('comments section shows New and Top sort tabs', async ({ page }) => {
  await page.goto(seedPosts.whyWriting.path);

  await expect(page.getByRole('navigation', { name: 'Comment sort' })).toBeVisible();
  const sortNav = page.getByRole('navigation', { name: 'Comment sort' });
  await expect(sortNav.getByRole('link', { name: 'New' })).toBeVisible();
  await expect(sortNav.getByRole('link', { name: 'Top' })).toBeVisible();
});

test('user can unlike a comment', async ({ page }) => {
  const commentText = `E2E unlike target ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto(seedPosts.whyWriting.path);

  await page.locator('#comment-body').fill(commentText);
  await page.getByRole('button', { name: 'Post comment' }).click();

  const comment = page.locator('#comments > article').filter({ hasText: commentText }).first();
  const likeBtn = comment.locator('.comment-like-btn');

  await likeBtn.click({ force: true });
  await expect(likeBtn.locator('.comment-like-count')).toHaveText('1');

  await likeBtn.click({ force: true });
  await expect(likeBtn.locator('.comment-like-count')).toHaveText('0');
});

test('new sort restores newest-first order', async ({ page }) => {
  const firstText = `E2E new sort first ${Date.now()}`;
  const secondText = `E2E new sort second ${Date.now()}`;

  await loginAsAuthor(page);
  await page.goto(seedPosts.whyWriting.path);

  await page.locator('#comment-body').fill(firstText);
  await page.getByRole('button', { name: 'Post comment' }).click();
  await expect(visibleCommentBody(page, firstText)).toBeVisible();
  await page.waitForTimeout(1100);
  await page.locator('#comment-body').fill(secondText);
  await page.getByRole('button', { name: 'Post comment' }).click();
  await expect(visibleCommentBody(page, secondText)).toBeVisible();

  await page.getByRole('navigation', { name: 'Comment sort' }).getByRole('link', { name: 'Top' }).click();
  await page.getByRole('navigation', { name: 'Comment sort' }).getByRole('link', { name: 'New' }).click();
  await expect(page).not.toHaveURL(/comments=top/);

  const secondComment = page.locator('#comments article').filter({ hasText: secondText });
  const firstComment = page.locator('#comments article').filter({ hasText: firstText });
  const secondBox = await secondComment.boundingBox();
  const firstBox = await firstComment.boundingBox();
  expect(secondBox!.y).toBeLessThan(firstBox!.y);
});
