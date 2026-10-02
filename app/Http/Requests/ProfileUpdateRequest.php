<?php

namespace App\Http\Requests;

use App\Models\AuthorAlias;
use App\Models\User;
use App\Support\AuthorSocialLinks;
use App\Support\CustomFieldSchema;
use App\Support\CustomFields;
use App\Support\SubdomainLabel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('username')) {
            $this->merge(['username' => SubdomainLabel::forNickname((string) $this->input('username'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique(User::class)->ignore($this->user()->id),
                Rule::unique(AuthorAlias::class, 'username')->ignore(
                    $this->user()->primaryAlias()->id ?? null,
                ),
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'bio' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'twitter_handle' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_]+$/'],
            'avatar' => ['nullable', 'image', 'max:1024'],
            'allow_comments' => ['sometimes', 'boolean'],
            ...AuthorSocialLinks::validationRules(),
            ...CustomFields::validationRules(CustomFieldSchema::CONTEXT_USER),
        ];
    }
}
