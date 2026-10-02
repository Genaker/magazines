<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\ValidatesCategoryHierarchy;
use App\Models\Magazine;
use App\Support\Features;
use Illuminate\Foundation\Http\FormRequest;

class StoreMagazineCategoryRequest extends FormRequest
{
    use ValidatesCategoryHierarchy;

    public function authorize(): bool
    {
        if (! Features::enabled('magazines')) {
            return false;
        }

        $magazine = $this->route('magazine');

        if (! $magazine instanceof Magazine) {
            return false;
        }

        return $this->user()?->can('update', $magazine) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Magazine $magazine */
        $magazine = $this->route('magazine');

        return $this->categoryFieldRules(magazineId: $magazine->id);
    }
}
