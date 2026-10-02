import { execSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { expect, Page } from '@playwright/test';
import { laravelDbEnv, playwrightAppEnv } from './db-env';

/** Canonical tenant demo seed (TenantDemoSeeder). */
export const tenantSeed = {
  slug: 'tenant1',
  host: 'tenant1.lvh.me',
  defaultHost: 'lvh.me',
  apexHost: 'lvh.me',
  authorEmail: 'author@tenant1.test',
  adminEmail: 'admin@tenant1.test',
  superAdminEmail: 'admin@magazines.test',
  authorUsername: 'tenant1author',
  magazineName: 'Tenant One Weekly',
  magazineSlug: 'tenant1-weekly',
  welcomePostTitle: 'Welcome to Tenant 1',
  password: 'password',
} as const;

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');

function e2eEnv(): Record<string, string> {
  return {
    ...process.env,
    ...laravelDbEnv(),
    ...playwrightAppEnv(),
    SESSION_DRIVER: 'array',
    CACHE_STORE: 'array',
    ENTITY_CACHE_STORE: 'array',
    MAIL_MAILER: 'array',
    QUEUE_CONNECTION: 'sync',
  };
}

export function tenantPort(): string {
  const base = process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8000';
  const match = base.match(/:(\d+)\/?$/);

  return match?.[1] ?? '8000';
}

export function tenantUrl(host: string, pathname = '/'): string {
  const path = pathname.startsWith('/') ? pathname : `/${pathname}`;

  return `http://${host}:${tenantPort()}${path}`;
}

export function tenantAuthorSubdomainUrl(pathname = '/'): string {
  return tenantUrl(`${tenantSeed.authorUsername}.${tenantSeed.host}`, pathname);
}

export function tenantMagazineSubdomainUrl(pathname = '/'): string {
  return tenantUrl(`${tenantSeed.magazineSlug}.${tenantSeed.host}`, pathname);
}

export function setTenantSubdomainsEnabled(enabled: boolean): void {
  const script = enabled
    ? "\\App\\Support\\Features::set('multi_tenancy', true); \\App\\Support\\Features::set('author_subdomains', true); \\App\\Support\\Features::set('magazine_subdomains', true); \\App\\Support\\AuthorSubdomain::setBaseHost('lvh.me'); \\App\\Support\\AuthorSubdomain::setRedirect(true); \\App\\Support\\MagazineSubdomain::setRedirect(true); \\App\\Support\\Tenancy\\Tenancy::flushRegisteredTenantHostsCache();"
    : "\\App\\Support\\Features::set('author_subdomains', false); \\App\\Support\\Features::set('magazine_subdomains', false); \\App\\Support\\Tenancy\\Tenancy::flushRegisteredTenantHostsCache();";

  execSync(`php artisan tinker --execute="${script}"`, {
    cwd: projectRoot,
    stdio: 'pipe',
    env: e2eEnv(),
  });
}

export async function loginOnTenantHost(
  page: Page,
  host: string,
  email: string,
  password = tenantSeed.password,
): Promise<void> {
  await page.goto(tenantUrl(host, '/login/password'));
  await expect(page.locator('#email')).toBeVisible();
  await page.locator('#email').fill(email);
  await page.locator('#password').fill(password);
  await page.getByRole('button', { name: /log in|увійти/i }).click();
  await expect(page).not.toHaveURL(/\/login/, { timeout: 15_000 });
}

export async function loginAsTenantAuthor(page: Page): Promise<void> {
  await loginOnTenantHost(page, tenantSeed.host, tenantSeed.authorEmail);
  await expect(page.getByRole('navigation').getByRole('link', { name: `@${tenantSeed.authorUsername}` })).toBeVisible();
}

export async function loginAsTenantAdmin(page: Page): Promise<void> {
  await loginOnTenantHost(page, tenantSeed.host, tenantSeed.adminEmail);
}

export async function loginAsSuperAdminOnDefault(page: Page): Promise<void> {
  await loginOnTenantHost(page, tenantSeed.defaultHost, tenantSeed.superAdminEmail);
}
