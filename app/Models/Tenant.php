<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    private static ?self $cachedDefault = null;

    private static ?self $cachedPlatform = null;

    protected $fillable = [
        'name',
        'slug',
        'is_default',
        'is_platform',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_platform' => 'boolean',
        ];
    }

    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function primaryHost(): ?string
    {
        $domains = $this->relationLoaded('domains')
            ? $this->domains
            : $this->domains()->get();

        return $domains->firstWhere('is_primary', true)?->host;
    }

    /** Default tenant with no domain — catch-all for unmapped hostnames. */
    public function isCatchAll(): bool
    {
        return $this->is_default && $this->primaryHost() === null;
    }

    public static function default(): self
    {
        return self::$cachedDefault ??= static::query()->where('is_default', true)->firstOrFail();
    }

    public static function platform(): self
    {
        return self::$cachedPlatform ??= static::query()->where('is_platform', true)->firstOrFail();
    }
}
