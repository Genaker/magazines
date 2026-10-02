<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\ValidatesCategoryHierarchy;
use Illuminate\Foundation\Http\FormRequest;

class StoreSiteCategoryRequest extends FormRequest
{
    use ValidatesCategoryHierarchy;

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->categoryFieldRules(magazineId: null);
    }
}
