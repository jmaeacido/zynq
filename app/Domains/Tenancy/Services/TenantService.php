<?php

namespace App\Domains\Tenancy\Services;

use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TenantService
{
    public function create(array $data): Tenant
    {
        return DB::transaction(fn () => Tenant::create($data));
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        return DB::transaction(function () use ($tenant, $data) {
            $tenant->update($data);

            return $tenant->refresh();
        });
    }
}
