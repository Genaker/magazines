<?php

namespace Tests\Unit;

use App\Support\DatabaseMigrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseMigratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_runs_migrations_in_process_without_artisan(): void
    {
        $migrator = app(DatabaseMigrator::class);

        $this->assertTrue($migrator->repositoryExists());
        $this->assertTrue(Schema::hasTable('users'));

        $ran = $migrator->run();

        $this->assertSame([], $ran);
    }
}
