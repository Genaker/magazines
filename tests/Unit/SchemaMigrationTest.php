<?php

namespace Tests\Unit;

use App\Support\SchemaMigration;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SchemaMigrationTest extends TestCase
{
    private array $createdFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $file) {
            File::delete($file);
        }

        parent::tearDown();
    }

    public function test_formats_version_prefixed_filename(): void
    {
        $this->assertSame(
            'V00049_mark_bootstrap_super_admin_aliases_as_system.php',
            SchemaMigration::formatFilename(49, 'mark_bootstrap_super_admin_aliases_as_system'),
        );
    }

    public function test_next_version_increments_from_existing_files(): void
    {
        $path = SchemaMigration::migrationsPath();
        $this->createdFiles[] = $path.'/V99998_example_one.php';
        $this->createdFiles[] = $path.'/V99999_example_two.php';
        File::put($this->createdFiles[0], '<?php');
        File::put($this->createdFiles[1], '<?php');

        $this->assertSame(99999, SchemaMigration::latestDefinedVersion());
        $this->assertSame(100000, SchemaMigration::nextVersion());
    }
}
