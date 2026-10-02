<?php

namespace Tests\Feature\Schema;

use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UsersPasswordColumnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        config(['app.debug' => true]);
    }

    public function test_users_password_column_allows_null_on_mysql(): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('MySQL/MariaDB schema assertion only.');
        }

        $column = DB::selectOne(
            'SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [Schema::getConnection()->getDatabaseName(), 'users', 'password']
        );

        $this->assertNotNull($column);
        $this->assertSame('YES', $column->IS_NULLABLE);
    }

    public function test_user_can_be_persisted_with_null_password(): void
    {
        $user = User::query()->create([
            'name' => 'Schema Passwordless User',
            'username' => 'schemapasswordless',
            'email' => 'schema-passwordless@example.com',
            'password' => null,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'schema-passwordless@example.com',
        ]);

        $this->assertNull(
            DB::table('users')->where('id', $user->id)->value('password')
        );
    }

    public function test_passwordless_registration_persists_null_password_in_database(): void
    {
        $response = $this->post('/register', [
            'name' => 'DB Passwordless User',
            'username' => 'dbpasswordless',
            'email' => 'db-passwordless@example.com',
            'magic_link_only' => '1',
        ]);

        $this->assertRedirectsToRegistrationCodeStep($response, 'db-passwordless@example.com');

        $this->assertNull(
            DB::table('users')->where('email', 'db-passwordless@example.com')->value('password')
        );
    }
}
