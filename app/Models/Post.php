<?php

namespace App\Models;

use App\Enums\MagazineSubmissionStatus;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\CachesQueries;
use App\Support\EntityCache;
use App\Support\EmbeddedMedia;
use App\Services\ImageService;
use App\Support\VideoEmbed;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use BelongsToTenant, CachesQueries, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'author_alias_id',
        'category_id',
        'title',
        'subtitle',
        'slug',
        'type',
        'video_url',
        'body',
        'cover_image',
        'cover_variants',
        'share_image',
        'share_image_variants',
        'magazine_id',
        'magazine_submission_status',
        'autosave_revision',
        'status',
        'reading_time',
        'views_count',
        'likes_count',
        'published_at',
        'subscription_notified_at',
        'pinned_at',
        'feed_hidden_at',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'type' => PostType::class,
            'status' => PostStatus::class,
            'magazine_submission_status' => MagazineSubmissionStatus::class,
            'cover_variants' => 'array',
            'share_image_variants' => 'array',
            'published_at' => 'datetime',
            'subscription_notified_at' => 'datetime',
            'pinned_at' => 'datetime',
            'feed_hidden_at' => 'datetime',
            'custom_fields' => 'array',
        ];
    }

    protected function body(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null ? null : EmbeddedMedia::normalize($value),
        );
    }

    public function isPinned(): bool
    {
        return $this->pinned_at !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withoutGlobalScope('tenant');
    }

    public function authorAlias(): BelongsTo
    {
        return $this->belongsTo(AuthorAlias::class)->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function magazine(): BelongsTo
    {
        return $this->belongsTo(Magazine::class);
    }

    public function autosaveSnapshots(): HasMany
    {
        return $this->hasMany(PostAutosaveSnapshot::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(PostView::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }

    public function readingLists(): BelongsToMany
    {
        return $this->belongsToMany(ReadingList::class, 'reading_list_post')
            ->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** @return HasMany<PostGalleryItem> */
    public function galleryItems(): HasMany
    {
        return $this->hasMany(PostGalleryItem::class)->orderBy('sort_order');
    }

    public function isGallery(): bool
    {
        return $this->type === PostType::Gallery;
    }

    public function isVideo(): bool
    {
        return $this->type === PostType::Video;
    }

    public function isArticle(): bool
    {
        return $this->type === PostType::Article;
    }

    /** Cover URL for feeds — custom cover or first gallery photo. */
    public function feedCoverUrl(): ?string
    {
        $images = app(ImageService::class);

        if ($this->cover_image) {
            return $images->url($this->cover_variants['md'] ?? $this->cover_image);
        }

        if ($this->isVideo() && filled($this->video_url)) {
            return VideoEmbed::thumbnailUrl($this->video_url);
        }

        if ($this->relationLoaded('galleryItems')) {
            $first = $this->galleryItems->first();
        } else {
            $first = $this->galleryItems()->orderBy('sort_order')->first();
        }

        return $first?->url('md');
    }

    public function isScheduled(): bool
    {
        return $this->status === PostStatus::Published
            && $this->published_at !== null
            && $this->published_at->isFuture();
    }

    public function isPublished(): bool
    {
        return $this->status === PostStatus::Published
            && $this->published_at !== null
            && $this->published_at <= now();
    }

    public static function uniqueSlugForAlias(string $title, int $aliasId, ?int $ignoreId = null): string
    {
        $base = \Illuminate\Support\Str::slug($title);
        $slug = $base;
        $counter = 1;

        while (static::query()
            ->where('author_alias_id', $aliasId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function scopePublished($query)
    {
        return $query
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /** Published stories shown on a public author profile (includes approved magazine posts). */
    public function scopeVisibleOnAuthorProfile($query)
    {
        return $query
            ->published()
            ->where(function ($builder) {
                $builder
                    ->whereNull('magazine_id')
                    ->orWhere('magazine_submission_status', MagazineSubmissionStatus::Approved);
            });
    }

    /** Pinned posts first; PostgreSQL puts NULLs first on DESC unless NULLS LAST is set. */
    public function scopeOrderByPinThenPublished($query)
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            return $query
                ->orderByRaw('pinned_at DESC NULLS LAST')
                ->orderByDesc('published_at');
        }

        return $query->orderByDesc('pinned_at')->orderByDesc('published_at');
    }

    public function scopeOrderByPinThenUpdated($query)
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            return $query
                ->orderByRaw('pinned_at DESC NULLS LAST')
                ->orderByDesc('updated_at');
        }

        return $query->orderByDesc('pinned_at')->orderByDesc('updated_at');
    }

    public function scopeVisibleInFeeds($query)
    {
        return $query
            ->whereNull('feed_hidden_at')
            ->where(function ($builder) {
                $builder
                    ->whereNull('magazine_id')
                    ->orWhere('magazine_submission_status', MagazineSubmissionStatus::Approved);
            });
    }

    public function isHiddenFromFeeds(): bool
    {
        return $this->feed_hidden_at !== null;
    }

    public static function resolveForShow(string $username, string $slug): self
    {
        $slug = trim($slug, '.');

        $aliasId = AuthorAlias::withTrashed()
            ->where('username', $username)
            ->value('id');

        if (! $aliasId) {
            abort(404);
        }

        return static::cachedForShow($aliasId, $slug);
    }

    public static function resolveForMagazineShow(Magazine $magazine, string $slug): self
    {
        $slug = trim($slug, '.');

        return static::rememberQuery(
            EntityCache::key('posts', 'magazine-show', $magazine->id, $slug),
            fn () => static::query()
                ->with(['user', 'authorAlias', 'category', 'tags', 'magazine', 'galleryItems'])
                ->where('magazine_id', $magazine->id)
                ->where('slug', $slug)
                ->where('magazine_submission_status', MagazineSubmissionStatus::Approved)
                ->firstOrFail(),
        );
    }

    public static function cachedForShow(int $aliasId, string $slug): self
    {
        $slug = trim($slug, '.');

        return static::rememberQuery(
            EntityCache::key('posts', 'show', $aliasId, $slug),
            fn () => static::query()
                ->with(['user', 'authorAlias', 'category', 'tags', 'magazine', 'galleryItems'])
                ->where('author_alias_id', $aliasId)
                ->where('slug', $slug)
                ->firstOrFail(),
        );
    }

    public static function cachedPublishedForCategory(int $categoryId, int $page = 1, int $perPage = 15)
    {
        $categoryIds = Category::descendantIdsFor($categoryId);

        return static::rememberQuery(
            EntityCache::key('posts', 'category', $categoryId, 'tree', 'page', $page),
            fn () => static::query()
                ->with(['user', 'authorAlias', 'tags', 'category'])
                ->whereIn('category_id', $categoryIds)
                ->published()
                ->visibleInFeeds()
                ->orderByDesc('published_at')
                ->paginate($perPage, ['*'], 'page', $page),
        );
    }

    public static function cachedPublishedForFeed(int $page = 1, int $perPage = 15)
    {
        return static::rememberQuery(
            EntityCache::key('posts', 'feed', 'latest', 'page', $page),
            fn () => static::query()
                ->with(['user', 'authorAlias', 'tags', 'category'])
                ->published()
                ->visibleInFeeds()
                ->orderByDesc('published_at')
                ->paginate($perPage, ['*'], 'page', $page),
        );
    }

    public static function moreFromAuthor(self $post, int $limit = 6)
    {
        if (! $post->author_alias_id) {
            return collect();
        }

        return static::query()
            ->with('authorAlias')
            ->visibleOnAuthorProfile()
            ->where('author_alias_id', $post->author_alias_id)
            ->whereKeyNot($post->id)
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    public function excerpt(int $length = 200): string
    {
        if (filled($this->subtitle)) {
            return $this->subtitle;
        }

        return \Illuminate\Support\Str::limit(strip_tags((string) $this->body), $length);
    }

    public static function cachedPublishedForTag(int $tagId, int $page = 1, int $perPage = 15)
    {
        return static::rememberQuery(
            EntityCache::key('posts', 'tag', $tagId, 'page', $page),
            fn () => static::query()
                ->with(['user', 'authorAlias', 'category'])
                ->published()
                ->visibleInFeeds()
                ->whereHas('tags', fn ($query) => $query->where('tags.id', $tagId))
                ->orderByDesc('published_at')
                ->paginate($perPage, ['*'], 'page', $page),
        );
    }
}
