<?php

namespace App\Http\Requests\Api\V1\Concerns;

use App\Models\Category;
use Illuminate\Validation\Rule;

trait ValidatesCategoryHierarchy
{
    /** @return array<string, array<int, mixed>> */
    protected function categoryFieldRules(?int $magazineId, ?int $excludeCategoryId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => $this->parentIdRule($magazineId, $excludeCategoryId),
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /** @return array<int, mixed> */
    private function parentIdRule(?int $magazineId, ?int $excludeCategoryId): array
    {
        return [
            'nullable',
            'integer',
            'exists:categories,id',
            Rule::notIn(array_filter([$excludeCategoryId])),
            function (string $attribute, mixed $value, \Closure $fail) use ($magazineId): void {
                if (! $value) {
                    return;
                }

                $parent = Category::query()->find($value);

                if (! $parent || ($parent->magazine_id ?? null) != ($magazineId ?: null)) {
                    $fail('Parent category must belong to the same scope.');
                }
            },
        ];
    }
}
