<?php

namespace Tests\Feature\Tenancy;

use App\Models\TenantDomain;
use Tests\TestCase;

class TenantDomainValidationTest extends TestCase
{
    public function test_valid_hosts_include_apex_and_subdomains(): void
    {
        $this->assertTrue(TenantDomain::isValidHost('lvh.me'));
        $this->assertTrue(TenantDomain::isValidHost('tenant1.lvh.me'));
        $this->assertTrue(TenantDomain::isValidHost('blog.example.com'));
        $this->assertTrue(TenantDomain::isValidHost('lvh2.me'));
    }

    public function test_invalid_hosts_are_rejected(): void
    {
        $this->assertFalse(TenantDomain::isValidHost(''));
        $this->assertFalse(TenantDomain::isValidHost('localhost'));
        $this->assertFalse(TenantDomain::isValidHost('127.0.0.1'));
        $this->assertFalse(TenantDomain::isValidHost('http://lvh.me'));
        $this->assertFalse(TenantDomain::isValidHost('-bad.lvh.me'));
    }

    public function test_normalize_host_strips_port(): void
    {
        $this->assertSame('lvh.me', TenantDomain::normalizeHost('lvh.me:8888'));
    }
}
