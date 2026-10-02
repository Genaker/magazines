<?php

namespace App\Models;

use App\Enums\MagazineSubmissionStatus;
use App\Enums\PostStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\CachesQueries;
use App\Support\EntityCache;
use App\Support\MagazineNavSettings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Magazine extends Model
{
    use BelongsToTenant, CachesQueries;
    use SoftDeletes;

    protected $attributes = [
        'require_post_approval' => true,
    ];

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'require_post_approval',
        'logo',
        'logo_variants',
        'custom_fields',
        'nav_pinned_at',
        'nav_sort_order',
    ];

    protected function casts(): array
    {
        return [
            'logo_variants' => 'array',
            'custom_fields' => 'array',
            'require_post_approval' => 'boolean',
            'nav_pinned_at' => 'datetime',
            'nav_sort_order' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id')->withoutGlobalScope('tenant');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'magazine_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('sort_order')->orderBy('name');
    }

    public function coverImageUrl(): ?string
    {
        $path = $this->logo_variants['lg'] ?? $this->logo_variants['md'] ?? $this->logo;

        return $path ? app(\App\Services\ImageService::class)->url($path) : null;
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'magazine_user')->withTimestamps();
    }

    public function joinRequests(): HasMany
    {
        return $this->hasMany(MagazineJoinRequest::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function memberRole(User $user): ?string
    {
        if ($this->owner_id === $user->id) {
            return 'owner';
        }

        return $this->members()->where('users.id', $user->id)->first()?->pivot?->role;
    }

    public function scopeWritableBy(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $builder) use ($user): void {
            $builder
                ->where('owner_id', $user->id)
                ->orWhereHas('members', fn (Builder $members) => $members->where('users.id', $user->id));
        });
    }

    public function isNavPinned(): bool
    {
        return $this->nav_pinned_at !== null;
    }

    /** @return array{items: Collection<int, static>, total: int, more_count: int} */
    public static function cachedNavigationPayload(): array
    {
        $limit = MagazineNavSettings::limit();
        $mode = MagazineNavSettings::mode();
        $activityDays = static::navActivityDays();

        return static::rememberQuery(
            EntityCache::key('magazines', 'navigation', 'payload', $limit, $mode, $activityDays, now()->format('Y-m-d')),
            fn () => static::buildNavigationPayload($limit, $mode),
        );
    }

    public static function cachedForNavigation(): Collection
    {
        return static::cachedNavigationPayload()['items'];
    }

    /** @return array{items: Collection<int, static>, total: int, more_count: int} */
    private static function buildNavigationPayload(int $limit, string $mode): array
    {
        $total = static::query()->count();

        if ($mode === MagazineNavSettings::MODE_AUTO) {
            $since = static::navActivitySince();

            $items = static::query()
                ->withCount(['posts as recent_posts_count' => fn ($query) => static::applyRecentNavPostCountConstraints($query, $since)])
                ->orderByDesc('recent_posts_count')
                ->orderBy('name')
                ->limit($limit)
                ->get();

            return [
                'items' => new Collection($items->all()),
                'total' => $total,
                'more_count' => max(0, $total - $items->count()),
            ];
        }

        $items = static::query()
            ->whereNotNull('nav_pinned_at')
            ->orderBy('nav_sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return [
            'items' => new Collection($items->all()),
            'total' => $total,
            'more_count' => max(0, $total - $items->count()),
        ];
    }

    private static function navActivityDays(): int
    {
        return max(1, min(30, (int) config('magazines.nav.activity_days', 7)));
    }

    private static function navActivitySince(): \Illuminate\Support\Carbon
    {
        return now()->subDays(static::navActivityDays());
    }

    private static function applyRecentNavPostCountConstraints($query, \Illuminate\Support\Carbon $since): void
    {
        $query
            ->where('status', PostStatus::Published)
            ->where('magazine_submission_status', MagazineSubmissionStatus::Approved)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('published_at', '>=', $since);
    }
}
