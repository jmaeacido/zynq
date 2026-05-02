<?php

namespace App\Domains\Sales\Models;

use App\Domains\Branches\Models\Branch;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Terminals\Models\Terminal;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id', 'branch_id', 'terminal_id', 'cashier_id', 'cash_session_id', 'sale_id',
    'idempotency_key', 'offline_reference', 'payload_hash', 'status', 'created_offline_at',
    'synced_at', 'payload', 'conflicts', 'last_error',
])]
class OfflineSaleSyncRecord extends Model
{
    protected function casts(): array
    {
        return [
            'created_offline_at' => 'datetime',
            'synced_at' => 'datetime',
            'payload' => 'array',
            'conflicts' => 'array',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function terminal(): BelongsTo { return $this->belongsTo(Terminal::class); }
    public function cashier(): BelongsTo { return $this->belongsTo(User::class, 'cashier_id'); }
    public function cashSession(): BelongsTo { return $this->belongsTo(CashSession::class); }
    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
}
