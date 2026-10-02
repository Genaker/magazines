<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\CachesQueries;
use App\Support\DatabaseFullTextSearch;
use App\Support\EntityCache;
use App\Support\SubdomainLabel;
use Database\Factories\AuthorAliasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuthorAlias extends Model
{
    /** @use HasFactory<AuthorAliasFactory> */
    use BelongsToTenant, CachesQueries, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'username',
        'name',
        'bio',
        'avatar',
        'website',
        'twitter_handle',
        'social_links',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'social_links' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $alias): void {
            if ($alias->isDirty('username')) {
                $alias->username = SubdomainLabel::forNickname((string) $alias->username);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'username';
    }

    public function resolveRouteBinding($value, $field = null): self
    {
        $value = SubdomainLabel::forNickname((string) $value);

        return $this->newQuery()
            ->withTrashed()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }

    public function isRetired(): bool
    {
        return $this->trashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withoutGlobalScope('tenant');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeListedInDirectory($query)
    {
        return $query->where(function ($builder) {
            $builder->whereHas('posts', fn ($postQuery) => $postQuery->visibleOnAuthorProfile())
                ->orWhereNotIn('username', self::bootstrapOperatorUsernames());
        });
    }

    /** @return list<string> Default install operator usernames — hidden from /authors until they publish. */
    public static function bootstrapOperatorUsernames(): array
    {
        return ['admin', 'superadmin', 'administrator'];
    }

    public function scopeMatchingSearch($query, string $term)
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function ($builder) use ($term): void {
            DatabaseFullTextSearch::matchAny($builder, ['name', 'username', 'bio'], $term);
        });
    }

    public static function createFromUser(User $user, bool $isPrimary = false): self
    {
        return static::query()->create([
            'user_id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'bio' => $user->bio,
            'avatar' => $user->avatar,
            'website' => $user->website,
            'twitter_handle' => $user->twitter_handle,
            'social_links' => $user->social_links,
            'is_primary' => $isPrimary,
        ]);
    }

    public function cachedAuthorStats(): array
    {
        return static::rememberQuery(
            EntityCache::key('aliases', 'stats', $this->id),
            fn () => [
                'articles' => $this->posts()->visibleOnAuthorProfile()->count(),
                'followers' => $this->user?->followers()->count() ?? 0,
                'following' => $this->user?->following()->count() ?? 0,
            ],
        );
    }

    public function cachedPublishedPosts(int $page = 1, int $perPage = 15)
    {
        return static::rememberQuery(
            EntityCache::key('aliases', 'posts', $this->id, 'page', $page, 'newest'),
            fn () => $this->posts()
                ->with(['category', 'tags', 'authorAlias'])
                ->visibleOnAuthorProfile()
                ->orderByDesc('published_at')
                ->paginate($perPage, ['*'], 'page', $page),
        );
    }
}
