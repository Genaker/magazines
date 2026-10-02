<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Post */
class PostResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'slug' => $this->slug,
            'status' => $this->status->value,
            'body' => $this->body,
            'category_id' => $this->category_id,
            'author_alias_id' => $this->author_alias_id,
            'magazine_id' => $this->magazine_id,
            'published_at' => $this->published_at?->toIso8601String(),
            'url' => route('posts.show', [$this->authorAlias, $this->slug]),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')->values()->all()),
            'custom_fields' => $this->custom_fields,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
