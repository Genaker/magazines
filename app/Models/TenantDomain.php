<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantDomain extends Model
{
    protected $fillable = [
        'tenant_id',
        'host',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function booted(): void
    {
        static::saved(function (self $domain): void {
            \App\Support\Tenancy\Tenancy::flushRegisteredTenantHostsCache();
            \App\Support\Tenancy\TenantResolver::flushHostCache($domain->host);
        });

        static::deleted(function (self $domain): void {
            \App\Support\Tenancy\Tenancy::flushRegisteredTenantHostsCache();
            \App\Support\Tenancy\TenantResolver::flushHostCache($domain->host);
        });
    }

    public static function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));

        if (preg_match('/:(\d+)$/', $host)) {
            $host = preg_replace('/:\d+$/', '', $host) ?? $host;
        }

        return $host;
    }

    public static function apexHost(string $host): string
    {
        $host = self::normalizeHost($host);
        $parts = explode('.', $host);

        if (count($parts) <= 2) {
            return $host;
        }

        return implode('.', array_slice($parts, -2));
    }

    /** Hostname for tenant routing — apex (lvh.me) or subdomain (tenant1.lvh.me). */
    public static function isValidHost(string $host): bool
    {
        $raw = strtolower(trim($host));

        if (str_contains($raw, '://') || str_contains($raw, '/')) {
            return false;
        }

        $host = self::normalizeHost($host);

        if ($host === '' || strlen($host) > 253) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (in_array($host, ['localhost'], true) || str_ends_with($host, '.localhost')) {
            return false;
        }

        if (str_contains($host, '://') || str_contains($host, '/')) {
            return false;
        }

        return preg_match(
            '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/',
            $host,
        ) === 1;
    }
}
