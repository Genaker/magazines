<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AuthorAlias;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::query()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'admin@magazines.test',
            'password' => Hash::make('password'),
            'role' => UserRole::SuperAdmin,
            'email_verified_at' => now(),
        ]);

        $superAdmin->forceFill(['tenant_id' => null])->saveQuietly();

        AuthorAlias::createFromUser($superAdmin, isPrimary: true);

        $this->call(DemoContentSeeder::class);
        $this->call(TenantDemoSeeder::class);
    }
}
