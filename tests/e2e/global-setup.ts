import { execSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { laravelDbEnv, playwrightAppEnv } from './helpers/db-env';

export default function globalSetup() {
  const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
  const env = {
    ...process.env,
    ...laravelDbEnv(),
    ...playwrightAppEnv(),
    SESSION_DRIVER: 'array',
    CACHE_STORE: 'array',
    ENTITY_CACHE_STORE: 'array',
    MAIL_MAILER: 'array',
    QUEUE_CONNECTION: 'sync',
    SEARCH_DRIVER: 'database',
  };

  execSync('php artisan migrate:fresh --seed --force', {
    cwd: root,
    stdio: 'inherit',
    env,
  });

  execSync(
    'php artisan tinker --execute="\\App\\Support\\HomeLayout::set(\\App\\Support\\HomeLayout::Discover); \\App\\Support\\SiteLocale::setEnabled([\'en\', \'ua\']); \\App\\Support\\SiteLocale::setDefault(\'en\'); \\App\\Support\\Features::set(\'multi_tenancy\', false); \\App\\Support\\Features::set(\'author_subdomains\', false); \\App\\Support\\Features::set(\'magazine_subdomains\', false);"',
    { cwd: root, stdio: 'inherit', env },
  );
}
