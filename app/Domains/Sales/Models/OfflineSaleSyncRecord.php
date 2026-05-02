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
    'synced_at', 'reviewed_at', 'reviewed_by_user_id', 'cancelled_at', 'cancelled_by_user_id',
    'payload', 'conflicts', 'last_error', 'resolution_reason', 'resolution_metadata',
])]
class OfflineSaleSyncRecord extends Model
{
    protected function casts(): array
    {
        return [
            'created_offline_at' => 'datetime',
            'synced_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'payload' => 'array',
            'conflicts' => 'array',
            'resolution_metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function terminal(): BelongsTo { return $this->belongsTo(Terminal::class); }
    public function cashier(): BelongsTo { return $this->belongsTo(User::class, 'cashier_id'); }
    public function reviewedBy(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by_user_id'); }
    public function cancelledBy(): BelongsTo { return $this->belongsTo(User::class, 'cancelled_by_user_id'); }
    public function cashSession(): BelongsTo { return $this->belongsTo(CashSession::class); }
    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending_sync', 'syncing', 'failed' => 'Pending Sync',
            'synced' => 'Synced',
            'conflict' => 'Conflict',
            'cancelled' => 'Cancelled',
            'reviewed' => 'Reviewed',
            default => ucfirst((string) $this->status),
        };
    }
}
