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

function runPhpScript(script: string): string {
  const file = join(tmpdir(), `e2e-php-${Date.now()}.php`);

  writeFileSync(file, `<?php\n${script}\n`);

  try {
    return execSync(`php ${JSON.stringify(file)}`, {
      cwd: projectRoot,
      env: artisanEnv,
      encoding: 'utf8',
    }).trim();
  } finally {
    unlinkSync(file);
  }
}

export function seedPasswordlessUser(email: string, username: string, name = 'E2E Passwordless User'): void {
  runPhpScript(`
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
$user = \\App\\Models\\User::query()->where('email', ${JSON.stringify(email)})->first();
if ($user) {
    $user->forceDelete();
}
$user = \\App\\Models\\User::factory()->create([
  'name' => ${JSON.stringify(name)},
  'username' => ${JSON.stringify(username)},
  'email' => ${JSON.stringify(email)},
  'password' => null,
  'email_verified_at' => now(),
]);
if (! $user->authorAliases()->exists()) {
    \\App\\Models\\AuthorAlias::createFromUser($user, isPrimary: true);
}
  `);
}

export function seedPasswordResetPath(email: string): string {
  const payload = JSON.parse(runPhpScript(`
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
$user = \\App\\Models\\User::query()->where('email', ${JSON.stringify(email)})->firstOrFail();
$token = Illuminate\\Support\\Facades\\Password::createToken($user);
echo json_encode(['token' => $token, 'email' => $user->email]);
  `)) as { token: string; email: string };

  return `/reset-password/${payload.token}?email=${encodeURIComponent(payload.email)}`;
}

export function seedUnverifiedUser(email: string, username: string, name = 'E2E Unverified User'): void {
  runPhpScript(`
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
$user = \\App\\Models\\User::query()->where('email', ${JSON.stringify(email)})->first();
if ($user) {
    $user->forceDelete();
}
$user = \\App\\Models\\User::factory()->unverified()->create([
  'name' => ${JSON.stringify(name)},
  'username' => ${JSON.stringify(username)},
  'email' => ${JSON.stringify(email)},
  'password' => Illuminate\\Support\\Facades\\Hash::make('password'),
]);
if (! $user->authorAliases()->exists()) {
    \\App\\Models\\AuthorAlias::createFromUser($user, isPrimary: true);
}
  `);
}

export function seedVerificationUrl(email: string): string {
  return runPhpScript(`
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
$user = \\App\\Models\\User::query()->where('email', ${JSON.stringify(email)})->firstOrFail();
echo \\App\\Support\\VerificationEmail::verificationUrl($user);
  `);
}

export function unlockMagicLogin(email: string): void {
  runPhpScript(`
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();
$kernel->call('app:user:unlock-magic-login', ['email' => ${JSON.stringify(email)}]);
  `);
}

/** Clears array cache (rate limits) shared by the long-running e2e web server. */
export function clearE2eApplicationCache(): void {
  runPhpScript(`
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
Illuminate\\Support\\Facades\\Cache::flush();
  `);
}
