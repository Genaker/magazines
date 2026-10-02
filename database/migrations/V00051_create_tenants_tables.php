<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_platform')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('tenant_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('host')->unique();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        DB::table('tenants')->insert([
            [
                'id' => 1,
                'name' => 'Default',
                'slug' => 'default',
                'is_default' => true,
                'is_platform' => false,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Platform',
                'slug' => 'platform',
                'is_default' => false,
                'is_platform' => true,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_domains');
        Schema::dropIfExists('tenants');
    }
};
