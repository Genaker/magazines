import { test, expect } from '@playwright/test';
import { loginAsAuthor } from '../helpers/auth';
import { seedPosts } from '../helpers/seed';

const seededPostTitle = seedPosts.whyWriting.title;
const seededPostSlug = seedPosts.whyWriting.slug;
const seededDraftTitle = seedPosts.draftLocalAi.title;

test('home page advertises RSS and Atom feeds', async ({ page }) => {
  await page.goto('/');

  await expect(page.locator('link[rel="alternate"][type="application/rss+xml"]')).toHaveAttribute(
    'href',
    /\/feed\.rss$/,
  );
  await expect(page.locator('link[rel="alternate"][type="application/atom+xml"]')).toHaveAttribute(
    'href',
    /\/feed\.atom$/,
  );
});

test('rss feed returns published stories', async ({ request }) => {
  const response = await request.get('/feed.rss');

  expect(response.ok()).toBeTruthy();
  expect(response.headers()['content-type']).toContain('application/xml');

  const body = await response.text();
  expect(body).toContain('<rss version="2.0"');
  expect(body).toContain(`<title>${seededPostTitle}</title>`);
  expect(body).toContain(seededPostSlug);
  expect(body).not.toContain(seededDraftTitle);
});

test('atom feed returns published stories', async ({ request }) => {
  const response = await request.get('/feed.atom');

  expect(response.ok()).toBeTruthy();
  expect(response.headers()['content-type']).toContain('application/xml');

  const body = await response.text();
  expect(body).toContain('<feed xmlns="http://www.w3.org/2005/Atom">');
  expect(body).toContain(`<title>${seededPostTitle}</title>`);
  expect(body).toContain(seededPostSlug);
  expect(body).not.toContain(seededDraftTitle);
});

test('sitemap lists key public urls', async ({ request, baseURL }) => {
  const response = await request.get('/sitemap.xml');

  expect(response.ok()).toBeTruthy();
  expect(response.headers()['content-type']).toContain('application/xml');

  const body = await response.text();
  expect(body).toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
  expect(body).toContain(`${baseURL}/authors</loc>`);
  expect(body).toContain('/category/technology</loc>');
  expect(body).toContain(`/${seededPostSlug}</loc>`);
});

test('robots.txt points to the sitemap', async ({ request }) => {
  const response = await request.get('/robots.txt');

  expect(response.ok()).toBeTruthy();
  expect(response.headers()['content-type']).toContain('text/plain');

  const body = await response.text();
  expect(body).toContain('User-agent: *');
  expect(body).toContain('Allow: /');
  expect(body).toContain('Sitemap: /sitemap.xml');
});

test('write and edit forms include social share image upload', async ({ page }) => {
  await loginAsAuthor(page);

  await page.goto('/write');
  await expect(page.getByText('Social share image')).toBeVisible();
  await expect(page.locator('#share-image')).toBeVisible();

  await page.goto('/me/posts');
  await page.getByRole('link', { name: seededPostTitle, exact: true }).click();
  await page.getByRole('link', { name: 'Edit' }).click();
  await expect(page.getByRole('heading', { name: 'Edit story' })).toBeVisible();
  await expect(page.getByText('Social share image')).toBeVisible();
  await expect(page.locator('#share-image')).toBeVisible();
});
