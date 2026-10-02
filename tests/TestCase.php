<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        if (class_exists(\App\Support\Tenancy\TenantContext::class)) {
            \App\Support\Tenancy\TenantContext::reset();
        }
    }

    /** Empty the database for install/seed CLI tests (ParadeDB postgis views break db:wipe --drop-views). */
    protected function wipeForInstallTests(): void
    {
        $options = ['--force' => true];

        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            $options['--drop-views'] = true;
        }

        $this->artisan('db:wipe', $options);
    }

    protected function assertRedirectsToVerificationNotice($response, string $email): void
    {
        $response->assertRedirect(
            route('verification.notice', ['email' => $email], absolute: false)
        );
    }

    protected function assertRedirectsToRegistrationCodeStep($response, string $email): void
    {
        $response->assertRedirect(route('login', absolute: false));
        $response->assertSessionHas('magic_login_email', $email);
        $response->assertSessionHas('status', 'registration-code-sent');
    }

    protected function completeRegistrationLogin(string $email, string $intendedRoute = 'home'): static
    {
        config(['app.debug' => true]);

        $this->post(route('login.magic.verify.code'), [
            'email' => $email,
            'code' => session('dev_login_code'),
        ])->assertRedirect(route($intendedRoute, absolute: false));

        return $this;
    }

    protected function actingAsAdmin(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null): static
    {
        \App\Support\AdminSession::configure();

        return $this->actingAs($user, $guard);
    }
}
