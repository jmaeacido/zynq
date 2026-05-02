<?php

namespace App\Domains\Tenancy\Models;

use App\Domains\Branches\Models\Branch;
use App\Domains\Settings\Models\TenantSetting;
use App\Domains\Terminals\Models\Terminal;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'business_name',
    'trade_name',
    'registered_address',
    'tin',
    'taxpayer_type',
    'bir_rdo_code',
    'contact_name',
    'contact_email',
    'contact_phone',
    'license_key',
    'license_status',
    'subscription_expires_at',
    'grace_period_days',
    'logo_path',
    'invoice_footer',
    'onboarding_completed_at',
    'active',
])]
class Tenant extends Model
{
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'subscription_expires_at' => 'date',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function terminals(): HasMany
    {
        return $this->hasMany(Terminal::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(TenantSetting::class);
    }
}
