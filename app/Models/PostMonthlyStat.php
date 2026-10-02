<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMonthlyStat extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'year',
        'month',
        'views',
        'likes',
        'comments',
        'saves',
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
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
