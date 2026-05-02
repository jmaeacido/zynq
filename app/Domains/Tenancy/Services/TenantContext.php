<?php

namespace App\Domains\Tenancy\Services;

use App\Domains\Tenancy\Models\Tenant;
use App\Models\User;

class TenantContext
{
    public function forUser(?User $user): ?Tenant
    {
        if (! $user || $user->hasRole('Super Admin')) {
            return null;
        }

        return $user->tenant;
    }

    public function scopeForUser($query, ?User $user)
    {
        if (! $user || $user->hasRole('Super Admin')) {
            return $query;
        }

        if ($query->getModel() instanceof Tenant) {
            return $query->whereKey($user->tenant_id);
        }

        return $query->where('tenant_id', $user->tenant_id);
    }
}
