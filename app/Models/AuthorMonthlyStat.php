<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorMonthlyStat extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'month',
        'views',
        'likes',
        'comments',
        'saves',
        'followers_gained',
        'subscribers_gained',
        'followers_total',
        'subscribers_total',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'views' => 'integer',
            'likes' => 'integer',
            'comments' => 'integer',
            'saves' => 'integer',
            'followers_gained' => 'integer',
            'subscribers_gained' => 'integer',
            'followers_total' => 'integer',
            'subscribers_total' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
