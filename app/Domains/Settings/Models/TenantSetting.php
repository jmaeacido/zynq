<?php

namespace App\Domains\Settings\Models;

use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'key', 'value', 'is_sensitive'])]
class TenantSetting extends Model
{
    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_sensitive' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
