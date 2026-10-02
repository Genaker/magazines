<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryRedirect extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'slug',
        'category_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public static function register(string $slug, Category $target): void
    {
        static::query()->updateOrCreate(
            ['slug' => $slug],
            ['category_id' => $target->id],
        );
    }
}
