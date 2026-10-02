<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tag_user')->withTimestamps();
    }

    public function moderators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tag_moderators')->withTimestamps();
    }

    public function getDisplayNameAttribute(): string
    {
        return '#'.ltrim($this->name, '#');
    }
}
