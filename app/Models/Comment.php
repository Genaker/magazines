<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    public const MAX_DEPTH = 8;

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'body',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }

    public function depth(): int
    {
        $depth = 0;
        $current = $this;

        while ($current->parent_id) {
            $depth++;
            $current = $current->relationLoaded('parent')
                ? $current->parent
                : static::query()->find($current->parent_id);

            if (! $current) {
                break;
            }
        }

        return $depth;
    }

    public static function treeForPost(Post $post, string $sort = 'new'): Collection
    {
        return self::treeForPostWithMeta($post, $sort)['tree'];
    }

    /**
     * @return array{tree: Collection, count: int, ids: list<int>}
     */
    public static function treeForPostWithMeta(Post $post, string $sort = 'new'): array
    {
        $comments = static::query()
            ->where('post_id', $post->id)
            ->with('user')
            ->get();

        return [
            'tree' => static::buildTree($comments, null, true, $sort),
            'count' => $comments->count(),
            'ids' => $comments->pluck('id')->all(),
        ];
    }

    /**
     * @param  Collection<int, Comment>  $comments
     */
    public static function buildTree(Collection $comments, ?int $parentId = null, bool $isRoot = true, string $sort = 'new'): Collection
    {
        $branch = $parentId === null
            ? $comments->whereNull('parent_id')
            : $comments->where('parent_id', $parentId);

        if ($isRoot) {
            $branch = $sort === 'top'
                ? $branch->sort(fn (Comment $a, Comment $b) => [$b->likes_count, $b->created_at->timestamp] <=> [$a->likes_count, $a->created_at->timestamp])
                : $branch->sortByDesc('created_at');
        } else {
            $branch = $branch->sortBy('created_at');
        }

        return $branch->values()->map(function (Comment $comment) use ($comments, $sort) {
            $comment->setRelation('replies', static::buildTree($comments, $comment->id, false, $sort));

            return $comment;
        });
    }
}
