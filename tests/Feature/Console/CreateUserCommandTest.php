<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_author_user(): void
    {
        $this->artisan('app:user:create', [
            '--no-interaction' => true,
            '--name' => 'Demo Author',
            '--username' => 'demoauthor',
            '--email' => 'author@example.test',
            '--password' => 'password',
            '--role' => 'author',
            '--bio' => 'Writes stories.',
        ])->assertSuccessful();

        $user = User::query()->where('email', 'author@example.test')->first();
        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame('Writes stories.', $user->bio);
    }

    public function test_creates_admin_user(): void
    {
        $this->artisan('app:user:create', [
            '--no-interaction' => true,
            '--name' => 'Site Admin',
            '--username' => 'siteadmin',
            '--email' => 'admin@example.test',
            '--password' => 'password',
            '--role' => 'admin',
        ])->assertSuccessful();

        $this->assertSame(UserRole::Admin, User::query()->where('email', 'admin@example.test')->first()->role);
    }
}
