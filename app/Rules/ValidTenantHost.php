<?php

namespace App\Rules;

use App\Models\TenantDomain;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidTenantHost implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! TenantDomain::isValidHost($value)) {
            $fail('Enter a valid hostname (e.g. lvh.me or tenant1.lvh.me), without http:// or a port.');
        }
    }
}
