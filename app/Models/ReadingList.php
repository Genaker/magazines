<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ReadingList extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_default',
        'is_private',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_private' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'reading_list_post')
            ->withTimestamps()
            ->orderByPivot('created_at', 'desc');
    }

    public static function defaultForUser(User $user): self
    {
        $list = $user->readingLists()->where('is_default', true)->first();

        if ($list) {
            return $list;
        }

        return $user->readingLists()->create([
            'name' => 'Reading list',
            'is_default' => true,
            'is_private' => true,
        ]);
    }
}
