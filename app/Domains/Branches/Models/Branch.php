<?php

namespace App\Domains\Branches\Models;

use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Terminals\Models\Terminal;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tenant_id', 'branch_name', 'branch_code', 'address', 'bir_registered_address', 'status'])]
class Branch extends Model
{
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function terminals(): HasMany
    {
        return $this->hasMany(Terminal::class);
    }
}
