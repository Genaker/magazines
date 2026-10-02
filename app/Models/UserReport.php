<?php

namespace App\Models;

use App\Enums\UserReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserReport extends Model
{
    protected $fillable = [
        'reporter_id',
        'reported_id',
        'reason',
        'status',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => UserReportStatus::class,
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reported(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_id');
    }
}
