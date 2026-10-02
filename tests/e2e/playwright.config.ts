import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig, devices } from '@playwright/test';
import { laravelDbEnv, playwrightAppEnv, playwrightBaseUrl } from './helpers/db-env';

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');

export default defineConfig({
  testDir: './',
  globalSetup: './global-setup.ts',
  fullyParallel: false,
  workers: 1,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  use: {
    baseURL: playwrightBaseUrl(),
    trace: 'on-first-retry',
  },
  webServer: {
    command: 'php artisan serve --port=8000',
    cwd: projectRoot,
    url: `${playwrightBaseUrl()}/login`,
    reuseExistingServer: false,
    timeout: 120_000,
    env: {
      ...process.env,
      ...laravelDbEnv(),
      ...playwrightAppEnv(),
      APP_PORT: '8000',
      APP_ENV: 'local',
      SESSION_DRIVER: 'file',
      CACHE_STORE: 'array',
      ENTITY_CACHE_STORE: 'array',
      MAIL_MAILER: 'array',
      QUEUE_CONNECTION: 'sync',
      SEARCH_DRIVER: 'database',
    },
  },
  projects: [
    {
      name: 'single-tenant',
      testIgnore: ['**/tenants/**', '**/admin/tenants.spec.ts'],
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'multi-tenant',
      testMatch: ['**/tenants/**', '**/admin/tenants.spec.ts'],
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
