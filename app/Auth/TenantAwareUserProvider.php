<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Arrayable;

/** Loads users for session auth without the tenant global scope. */
class TenantAwareUserProvider extends EloquentUserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        $model = $this->createModel();

        return $this->newTenantAwareQuery($model)
            ->where($model->getAuthIdentifierName(), $identifier)
            ->first();
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?Authenticatable
    {
        $model = $this->createModel();

        $retrievedModel = $this->newTenantAwareQuery($model)
            ->where($model->getAuthIdentifierName(), $identifier)
            ->first();

        if (! $retrievedModel) {
            return null;
        }

        $rememberToken = $retrievedModel->getRememberToken();

        return $rememberToken && hash_equals($rememberToken, $token) ? $retrievedModel : null;
    }

    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        $credentials = array_filter(
            $credentials,
            fn ($key) => ! str_contains($key, 'password'),
            ARRAY_FILTER_USE_KEY,
        );

        if ($credentials === []) {
            return null;
        }

        $query = $this->newTenantAwareQuery($this->createModel());

        foreach ($credentials as $key => $value) {
            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            }

            if (is_array($value)) {
                $query->whereIn($key, $value);

                continue;
            }

            if ($key === 'email' && is_string($value)) {
                $value = strtolower(trim($value));
            }

            $query->where($key, $value);
        }

        return $query->first();
    }

    private function newTenantAwareQuery(Authenticatable $model)
    {
        return $model->newQuery()->withoutGlobalScope('tenant');
    }
}
