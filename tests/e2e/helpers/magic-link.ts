import { execSync } from 'node:child_process';
import { unlinkSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { laravelDbEnv } from './db-env';

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');

const artisanEnv = {
  ...process.env,
  ...laravelDbEnv(),
  MAIL_MAILER: 'array',
};

function runPhpScript(script: string): void {
  const file = join(tmpdir(), `e2e-magic-${Date.now()}.php`);

  writeFileSync(file, `<?php\n${script}\n`);

  try {
    execSync(`php ${JSON.stringify(file)}`, {
      cwd: projectRoot,
      env: artisanEnv,
    });
  } finally {
    unlinkSync(file);
  }
}

export function seedMagicLoginLink(email: string, token = 'e2e-magic-token', code = '112233'): string {
  const tokenHash = execSync(`php -r 'echo hash("sha256", ${JSON.stringify(token)});'`, {
    cwd: projectRoot,
    encoding: 'utf8',
  }).trim();

  const codeHash = execSync(`php -r 'echo hash("sha256", ${JSON.stringify(code)});'`, {
    cwd: projectRoot,
    encoding: 'utf8',
  }).trim();

  runPhpScript(`
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
\\App\\Models\\LoginLink::query()->create([
  'email' => ${JSON.stringify(email)},
  'token' => ${JSON.stringify(tokenHash)},
  'code' => ${JSON.stringify(codeHash)},
  'expires_at' => now()->addMinutes(15),
]);
  `);

  return `/login/magic-link/verify?email=${encodeURIComponent(email)}&token=${token}`;
}

export function seedMagicLoginCode(email: string, code = '112233'): string {
  seedMagicLoginLink(email, 'unused-e2e-token', code);
  return code;
}
