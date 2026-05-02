<?php

namespace App\Domains\Invoicing\Models;

use App\Domains\Branches\Models\Branch;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Terminals\Models\Terminal;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id', 'branch_id', 'terminal_id', 'document_type', 'range_start', 'range_end', 'next_number',
    'status', 'reserved_by_user_id', 'reserved_at', 'expires_at', 'consumed_at', 'metadata',
])]
class OfflineInvoiceRange extends Model
{
    protected function casts(): array
    {
        return [
            'reserved_at' => 'datetime',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function terminal(): BelongsTo { return $this->belongsTo(Terminal::class); }
    public function reservedBy(): BelongsTo { return $this->belongsTo(User::class, 'reserved_by_user_id'); }
}
