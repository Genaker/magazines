<?php

namespace App\Models;

use App\Enums\MagazineJoinRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MagazineJoinRequest extends Model
{
    protected $fillable = [
        'magazine_id',
        'user_id',
        'message',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MagazineJoinRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function magazine(): BelongsTo
    {
        return $this->belongsTo(Magazine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === MagazineJoinRequestStatus::Pending;
    }
}
