<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostRedirect extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'author_username',
        'slug',
        'post_id',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public static function register(string $authorUsername, string $slug, Post $post): void
    {
        $post->loadMissing('authorAlias');

        if (! $post->authorAlias) {
            return;
        }

        if ($authorUsername === $post->authorAlias->username && $slug === $post->slug) {
            return;
        }

        static::query()->updateOrCreate(
            [
                'author_username' => $authorUsername,
                'slug' => $slug,
            ],
            ['post_id' => $post->id],
        );
    }
}
