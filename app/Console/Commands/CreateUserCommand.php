<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Support\AppInstaller;
use App\Support\SiteBranding;
use App\Support\SubdomainLabel;
use App\Support\UserProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class CreateUserCommand extends Command
{
    protected $signature = 'app:user:create
        {--name= : Display name}
        {--username= : Unique username}
        {--email= : Email address}
        {--password= : Password (plain text; hashed automatically)}
        {--role=user : user, admin, or super_admin}
        {--bio= : Optional author bio}
        {--unverified : Do not mark email as verified}';

    protected $description = 'Create a user account (author, admin, or super-admin)';

    public function handle(UserProvisioner $provisioner, AppInstaller $installer): int
    {
        if (! Schema::hasTable('users')) {
            $this->components->error('Users table missing. Run php artisan app:install or migrate first.');

            return self::FAILURE;
        }

        if (! $installer->isInstalled()) {
            $this->components->warn('No users yet — run php artisan app:install for first-time setup.');
        }

        try {
            $role = $this->parseRole((string) $this->option('role'));
            $payload = $this->buildUserPayload();
            $user = $provisioner->create(
                $payload,
                $role,
                verified: ! $this->option('unverified'),
            );
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->success("User created: {$user->email} (@{$user->username}) [{$role->value}]");

        return self::SUCCESS;
    }

    /** @return array{name: string, username: string, email: string, password: string, bio?: string|null} */
    private function buildUserPayload(): array
    {
        $name = $this->option('name') ?: $this->prompt('Name');
        $username = $this->option('username') ?: $this->prompt('Username');
        $email = $this->option('email') ?: $this->prompt('Email');
        $password = $this->option('password') ?: $this->promptPassword();

        $payload = [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
        ];

        if ($bio = $this->option('bio')) {
            $payload['bio'] = $bio;
        }

        $payload['username'] = SubdomainLabel::forNickname($payload['username']);

        validator($payload, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'password' => ['required', Password::defaults()],
        ])->validate();

        return $payload;
    }

    private function parseRole(string $role): UserRole
    {
        $role = match ($role) {
            'author' => UserRole::User->value,
            default => $role,
        };

        $parsed = UserRole::tryFrom($role);

        if ($parsed === null) {
            throw new RuntimeException('Role must be user, author, admin, or super_admin.');
        }

        return $parsed;
    }

    private function prompt(string $label): string
    {
        if ($this->option('no-interaction')) {
            throw new RuntimeException("Provide --{$this->kebab($label)} when using --no-interaction.");
        }

        do {
            $value = (string) $this->ask($label);
        } while ($value === '');

        return $value;
    }

    private function promptPassword(): string
    {
        if ($this->option('no-interaction')) {
            throw new RuntimeException('Provide --password when using --no-interaction.');
        }

        do {
            $password = (string) $this->secret('Password');
            $confirm = (string) $this->secret('Confirm password');
        } while ($password === '' || $password !== $confirm);

        return $password;
    }

    private function kebab(string $label): string
    {
        return str_replace(' ', '-', strtolower($label));
    }
}
