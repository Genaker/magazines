<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Post::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['required', Rule::in(['draft', 'published', 'unlisted'])],
            'publish_at' => ['nullable', 'date'],
            'author_alias_id' => [
                'nullable',
                'integer',
                Rule::exists('author_aliases', 'id')->where(fn ($query) => $query->where('user_id', $user?->id)),
            ],
            'magazine_id' => ['nullable', 'integer', 'exists:magazines,id'],
            'tags' => ['nullable', 'array', 'max:'.config('media.max_tags_per_post', 10)],
            'tags.*' => ['string', 'max:100'],
            'custom_fields' => ['nullable', 'array'],
        ];
    }
}
