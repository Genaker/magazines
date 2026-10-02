<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Support\AdminPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AdminPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_admin_path_is_admin(): void
    {
        $this->assertSame('admin', AdminPath::prefix());
        $this->assertSame('/admin', AdminPath::cookiePath());
        $this->assertTrue(AdminPath::usesDefault());
    }

    public function test_admin_routes_use_configured_prefix(): void
    {
        $this->get(route('admin.login'))->assertOk();
        $this->assertStringContainsString('/admin/login', route('admin.login'));
    }

    public function test_normalize_rejects_public_route_conflicts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AdminPath::normalize('login');
    }

    public function test_site_setting_can_store_custom_admin_path(): void
    {
        AdminPath::set('desk');

        $this->assertSame('desk', SiteSetting::getPlatformValue('admin_path'));
        $this->assertSame('desk', AdminPath::raw());
        $this->assertFalse(AdminPath::usesDefault());
    }

    public function test_setting_default_admin_path_clears_override(): void
    {
        AdminPath::set('desk');
        AdminPath::set('admin');

        $this->assertNull(SiteSetting::getPlatformValue('admin_path'));
    }
}
