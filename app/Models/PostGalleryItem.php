<?php

namespace App\Models;

use App\Services\ImageService;
use App\Support\MediaSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostGalleryItem extends Model
{
    protected $fillable = [
        'post_id',
        'path',
        'variants',
        'sort_order',
        'caption',
    ];

    protected function casts(): array
    {
        return [
            'variants' => 'array',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /** Public URL for the primary display size. */
    public function url(?string $variant = 'md'): ?string
    {
        $variants = $this->variants ?? [];
        $path = $variants[$variant] ?? $variants['lg'] ?? $variants['sm'] ?? $this->path;

        return app(ImageService::class)->url($path);
    }

    /** HTML srcset for responsive loading in the portfolio grid. */
    public function srcset(): ?string
    {
        return app(ImageService::class)->responsiveSrcset($this->variants, MediaSettings::PRESET_GALLERY);
    }

    /** Alt text for img tags and accessibility. */
    public function altText(string $fallback): string
    {
        return filled($this->caption) ? $this->caption : $fallback;
    }
}
