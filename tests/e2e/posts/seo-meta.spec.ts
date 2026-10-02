import { test, expect } from '@playwright/test';
import { seedPosts, seedMagazine } from '../helpers/seed';

test('published post includes Open Graph and Twitter meta tags', async ({ page }) => {
  await page.goto(seedPosts.whyWriting.path);

  const ogTitle = `${seedPosts.whyWriting.title} · ${seedMagazine.name}`;
  await expect(page.locator('meta[property="og:title"]')).toHaveAttribute('content', ogTitle);
  await expect(page.locator('meta[property="og:description"]')).toHaveAttribute('content', 'Sharing ideas beyond the draft folder');
  await expect(page.locator('meta[property="og:type"]')).toHaveAttribute('content', 'article');
  await expect(page.locator('meta[property="article:section"]')).toHaveAttribute('content', /.+/);
  await expect(page.locator('meta[property="article:modified_time"]')).toHaveAttribute('content', /.+/);
  await expect(page.locator('link[rel="alternate"][type="application/rss+xml"]')).toHaveAttribute('href', /feed\.rss$/);
  await expect(page.locator('link[rel="alternate"][type="application/atom+xml"]')).toHaveAttribute('href', /feed\.atom$/);
  await expect(page.locator('meta[name="twitter:title"]')).toHaveAttribute('content', ogTitle);
  await expect(page.locator('meta[property="og:url"]')).toHaveAttribute('content', new RegExp(`${seedPosts.whyWriting.slug}$`));
  await expect(page.locator('link[rel="canonical"]')).toHaveAttribute('href', new RegExp(`${seedPosts.whyWriting.slug}$`));
  await expect(page.locator('meta[property="og:image"]')).toHaveAttribute('content', /.+/);
  await expect(page.locator('meta[name="twitter:image"]')).toHaveAttribute('content', /.+/);
  await expect(page.locator('meta[name="twitter:card"]')).toHaveAttribute('content', 'summary_large_image');
});
