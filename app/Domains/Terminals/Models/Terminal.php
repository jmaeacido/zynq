<?php

namespace App\Domains\Terminals\Models;

use App\Domains\Branches\Models\Branch;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'branch_id',
    'terminal_name',
    'terminal_code',
    'machine_identification_number',
    'serial_number',
    'permit_to_use_number',
    'accreditation_number',
    'software_version',
    'active',
])]
class Terminal extends Model
{
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
