<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\MagicLoginLock;
use Illuminate\Console\Command;

class UnlockMagicLoginCommand extends Command
{
    protected $signature = 'app:user:unlock-magic-login {email : User email address}';

    protected $description = 'Clear magic-link failed attempts and unlock sign-in for a user';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->components->error('No user found with that email.');

            return self::FAILURE;
        }

        if (! MagicLoginLock::isLocked($user) && $user->magic_login_failed_attempts === 0) {
            $this->components->info("User {$user->email} is not locked (0 failed attempts).");

            return self::SUCCESS;
        }

        MagicLoginLock::unlock($user);

        $this->components->success("Magic-link sign-in unlocked for {$user->email}.");

        return self::SUCCESS;
    }
}
