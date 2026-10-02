<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\CachesQueries;
use App\Support\EntityCache;
use App\Support\SubdomainLabel;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'username',
    'email',
    'password',
    'role',
    'bio',
    'avatar',
    'website',
    'twitter_handle',
    'social_links',
    'is_banned',
    'allow_comments',
    'custom_fields',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use BelongsToTenant, CachesQueries, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_banned' => 'boolean',
            'allow_comments' => 'boolean',
            'custom_fields' => 'array',
            'social_links' => 'array',
        ];
    }

    protected static function assignsTenantOnCreate(Model $model): bool
    {
        if (! $model instanceof self) {
            return true;
        }

        return $model->role !== UserRole::SuperAdmin;
    }

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if ($user->isDirty('username')) {
                $user->username = SubdomainLabel::forNickname((string) $user->username);
            }
        });
    }

    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'blocker_id', 'blocked_id')->withTimestamps();
    }

    public function blockedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'blocked_id', 'blocker_id')->withTimestamps();
    }

    public function hasBlocked(User $user): bool
    {
        return $this->blockedUsers()->where('blocked_id', $user->id)->exists();
    }

    public function hasPassword(): bool
    {
        return filled($this->getRawOriginal('password'));
    }

    public function isBlockedBy(User $user): bool
    {
        return $user->hasBlocked($this);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function authorAliases(): HasMany
    {
        return $this->hasMany(AuthorAlias::class);
    }

    public function primaryAlias(): AuthorAlias
    {
        return $this->authorAliases()
            ->where('is_primary', true)
            ->orderBy('id')
            ->firstOrFail();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function readingLists(): HasMany
    {
        return $this->hasMany(ReadingList::class);
    }

    public function hasPostInAnyList(Post $post): bool
    {
        return $this->readingLists()
            ->whereHas('posts', fn ($query) => $query->where('posts.id', $post->id))
            ->exists();
    }

    public function categoryRequests(): HasMany
    {
        return $this->hasMany(CategoryRequest::class);
    }

    public function reportsMade(): HasMany
    {
        return $this->hasMany(UserReport::class, 'reporter_id');
    }

    public function reportsReceived(): HasMany
    {
        return $this->hasMany(UserReport::class, 'reported_id');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')->withTimestamps();
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')->withTimestamps();
    }

    public function storySubscriptions(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'author_subscriptions', 'subscriber_id', 'author_id')
            ->withPivot('email_delivery')
            ->withTimestamps();
    }

    public function storySubscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'author_subscriptions', 'author_id', 'subscriber_id')
            ->withPivot('email_delivery')
            ->withTimestamps();
    }

    public function followedCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_user')->withTimestamps();
    }

    public function followedTags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tag_user')->withTimestamps();
    }

    public function moderatedCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_moderators')->withTimestamps();
    }

    public function moderatedTags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tag_moderators')->withTimestamps();
    }

    public function followedMagazines(): BelongsToMany
    {
        return $this->belongsToMany(Magazine::class, 'magazine_user')->withTimestamps();
    }

    public function magazineJoinRequests(): HasMany
    {
        return $this->hasMany(MagazineJoinRequest::class);
    }

    public function ownedMagazines(): HasMany
    {
        return $this->hasMany(Magazine::class, 'owner_id');
    }

    public function magazines(): BelongsToMany
    {
        return $this->belongsToMany(Magazine::class, 'magazine_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->role?->isAdmin() ?? false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function cachedAuthorStats(): array
    {
        return static::rememberQuery(
            EntityCache::key('authors', 'stats', $this->id),
            fn () => [
                'articles' => $this->posts()->visibleOnAuthorProfile()->count(),
                'followers' => $this->followers()->count(),
                'following' => $this->following()->count(),
            ],
        );
    }

    public function cachedPublishedPosts(int $page = 1, int $perPage = 15)
    {
        return static::rememberQuery(
            EntityCache::key('authors', 'posts', $this->id, 'page', $page, 'pinned'),
            fn () => $this->posts()
                ->with(['category', 'tags'])
                ->visibleOnAuthorProfile()
                ->orderByPinThenPublished()
                ->paginate($perPage, ['*'], 'page', $page),
        );
    }
}
