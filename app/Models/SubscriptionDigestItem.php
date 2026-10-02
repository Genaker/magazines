<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionDigestItem extends Model
{
    protected $fillable = [
        'subscriber_id',
        'post_id',
    ];

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subscriber_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
