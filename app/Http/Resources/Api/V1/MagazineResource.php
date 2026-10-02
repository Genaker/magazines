<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Magazine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Magazine */
class MagazineResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'owner_id' => $this->owner_id,
            'url' => route('magazines.show', $this->slug),
            'custom_fields' => $this->custom_fields,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
