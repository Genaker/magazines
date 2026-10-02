/** Laravel DB env for Playwright e2e (MariaDB via Docker host port). */
export function laravelDbEnv(): Record<string, string> {
  return {
    DB_CONNECTION: process.env.DB_CONNECTION ?? 'mysql',
    DB_HOST: process.env.DB_HOST ?? '127.0.0.1',
    DB_PORT: process.env.DB_PORT ?? '13307',
    DB_DATABASE: process.env.DB_DATABASE ?? 'magazines_e2e',
    DB_USERNAME: process.env.DB_USERNAME ?? 'root',
    DB_PASSWORD: process.env.DB_PASSWORD ?? 'secret',
  };
}

/** Match Playwright base URL so login forms and sessions stay on the test server. */
export function playwrightAppEnv(): Record<string, string> {
  const baseUrl = process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8000';

  return {
    APP_URL: process.env.PLAYWRIGHT_APP_URL ?? baseUrl,
    APP_PORT: process.env.APP_PORT ?? '8000',
    SESSION_DOMAIN: '',
  };
}

export function playwrightBaseUrl(): string {
  return process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8000';
}
