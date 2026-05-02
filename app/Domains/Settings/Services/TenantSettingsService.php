<?php

namespace App\Domains\Settings\Services;

use App\Domains\Settings\Models\TenantSetting;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TenantSettingsService
{
    public function set(Tenant $tenant, string $key, mixed $value, bool $sensitive = false): TenantSetting
    {
        return DB::transaction(fn () => TenantSetting::updateOrCreate(
            ['tenant_id' => $tenant->id, 'key' => $key],
            ['value' => $value, 'is_sensitive' => $sensitive],
        ));
    }
}
