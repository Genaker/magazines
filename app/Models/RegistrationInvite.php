<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationInvite extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'code',
        'created_by',
        'max_uses',
        'uses_count',
        'expires_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValid(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function hasUsesRemaining(): bool
    {
        return $this->max_uses === null || $this->uses_count < $this->max_uses;
    }

    public function redeem(): void
    {
        $this->increment('uses_count');
    }
}
