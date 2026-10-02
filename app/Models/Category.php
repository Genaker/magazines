<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\CachesQueries;
use App\Support\EntityCache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;

class Category extends Model
{
    use BelongsToTenant, CachesQueries;

    protected $fillable = [
        'parent_id',
        'magazine_id',
        'name',
        'slug',
        'description',
        'icon',
        'image',
        'image_variants',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'image_variants' => 'array',
        ];
    }

    public function magazine(): BelongsTo
    {
        return $this->belongsTo(Magazine::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'category_user')->withTimestamps();
    }

    public function moderators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'category_moderators')->withTimestamps();
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function imageUrl(): ?string
    {
        $path = $this->image_variants['md'] ?? $this->image_variants['sm'] ?? $this->image;

        return $path ? app(\App\Services\ImageService::class)->url($path) : null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function descendantIdsFor(int $categoryId): array
    {
        $all = static::query()->get(['id', 'parent_id']);
        $ids = [$categoryId];
        $queue = [$categoryId];

        while ($queue !== []) {
            $current = array_shift($queue);

            foreach ($all->where('parent_id', $current) as $child) {
                $ids[] = $child->id;
                $queue[] = $child->id;
            }
        }

        return array_values(array_unique($ids));
    }

    public static function cachedForNavigation(): Collection
    {
        $categories = static::rememberQuery(
            EntityCache::key('categories', 'navigation'),
            fn () => static::query()
                ->whereNull('magazine_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        );

        return static::buildTree($categories);
    }

    public static function cachedForSelect(): Collection
    {
        return static::cachedForNavigation();
    }

    public static function cachedForMagazine(int $magazineId): Collection
    {
        $categories = static::rememberQuery(
            EntityCache::key('categories', 'magazine', $magazineId),
            fn () => static::query()
                ->where('magazine_id', $magazineId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        );

        return static::buildTree($categories);
    }

    /** @return SupportCollection<int, Collection> */
    public static function magazineCategoryTrees(): SupportCollection
    {
        return static::query()
            ->whereNotNull('magazine_id')
            ->orderBy('magazine_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('magazine_id')
            ->map(fn (Collection $categories) => static::buildTree($categories));
    }

    public static function buildTree(Collection $categories): Collection
    {
        $grouped = $categories->groupBy('parent_id');

        $attachChildren = function (Category $category) use ($grouped, &$attachChildren): Category {
            $children = $grouped->get($category->id, collect())->values();
            $children->each(fn (Category $child) => $attachChildren($child));
            $category->setRelation('children', $children);

            return $category;
        };

        return $categories
            ->whereNull('parent_id')
            ->values()
            ->each(fn (Category $category) => $attachChildren($category));
    }

    public static function validParentOptions(?Category $exclude = null, ?int $magazineId = null): SupportCollection
    {
        $roots = $magazineId
            ? static::cachedForMagazine($magazineId)
            : static::cachedForNavigation();

        return static::flattenForSelect($roots, 0, $exclude);
    }

    public function isAllowedForPost(?int $magazineId): bool
    {
        if ($this->magazine_id === null) {
            return true;
        }

        return $magazineId !== null && $this->magazine_id === $magazineId;
    }

    public static function flattenForSelect(
        SupportCollection $categories,
        int $depth = 0,
        ?Category $exclude = null,
    ): SupportCollection {
        return $categories->flatMap(function (Category $category) use ($depth, $exclude) {
            if ($exclude && ($category->id === $exclude->id || static::isDescendantOf($category, $exclude))) {
                return collect();
            }

            $category->depth = $depth;

            return collect([$category])->merge(
                static::flattenForSelect($category->children, $depth + 1, $exclude),
            );
        });
    }

    public static function isDescendantOf(Category $candidate, Category $ancestor): bool
    {
        $parentId = $candidate->parent_id;

        while ($parentId !== null) {
            if ($parentId === $ancestor->id) {
                return true;
            }

            $parentId = static::query()->whereKey($parentId)->value('parent_id');
        }

        return false;
    }
}
