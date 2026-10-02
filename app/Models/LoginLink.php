<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLink extends Model
{
    protected $fillable = [
        'email',
        'token',
        'code',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function isValid(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }
}
