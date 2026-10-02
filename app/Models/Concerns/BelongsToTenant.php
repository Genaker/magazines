<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Automatic tenant_id scoping and assignment. */
trait BelongsToTenant
{
    protected static function assignsTenantOnCreate(Model $model): bool
    {
        return true;
    }

    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = TenantContext::scopeTenantId();

            if ($tenantId === null) {
                return;
            }

            $builder->where(
                $builder->getModel()->getTable().'.tenant_id',
                $tenantId,
            );
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('tenant_id') !== null) {
                return;
            }

            if (! static::assignsTenantOnCreate($model)) {
                return;
            }

            $model->setAttribute('tenant_id', TenantContext::effectiveIdForWrite());
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
