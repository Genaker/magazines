<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Magazine;
use App\Support\CustomFieldSchema;
use App\Support\CustomFields;
use App\Support\Features;
use Illuminate\Foundation\Http\FormRequest;

class StoreMagazineRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! Features::enabled('magazines')) {
            return false;
        }

        return $this->user()?->can('create', Magazine::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            ...CustomFields::validationRules(CustomFieldSchema::CONTEXT_MAGAZINE),
        ];
    }
}
