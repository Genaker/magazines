<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SeedDemoCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['installer.enforce_in_tests' => true]);
        File::delete(storage_path('app/.installed'));

        $this->wipeForInstallTests();
    }

    protected function tearDown(): void
    {
        File::delete(storage_path('app/.installed'));
        config(['installer.enforce_in_tests' => false]);

        try {
            $this->artisan('migrate:fresh', ['--force' => true]);
        } catch (\Throwable) {
            // Fresh database may not exist yet when seed tests fail early.
        }

        \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = false;
        \Illuminate\Foundation\Testing\RefreshDatabaseState::$lazilyRefreshed = false;

        parent::tearDown();
    }

    public function test_seeds_demo_on_empty_database(): void
    {
        $this->artisan('app:seed-demo', ['--no-interaction' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'admin@magazines.test']);
        $this->assertDatabaseHas('users', ['email' => 'author@magazines.test']);
    }

    public function test_refuses_when_users_exist_without_force(): void
    {
        $this->artisan('app:seed-demo', ['--no-interaction' => true])->assertSuccessful();

        $this->artisan('app:seed-demo', ['--no-interaction' => true])
            ->assertFailed();
    }
}
