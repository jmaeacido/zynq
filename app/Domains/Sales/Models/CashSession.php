<?php

namespace App\Domains\Sales\Models;

use App\Domains\Branches\Models\Branch;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Terminals\Models\Terminal;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'tenant_id',
    'branch_id',
    'terminal_id',
    'user_id',
    'status',
    'opening_cash',
    'expected_cash',
    'actual_cash',
    'cash_difference',
    'opened_at',
    'closed_at',
    'metadata',
])]
class CashSession extends Model
{
    protected function casts(): array
    {
        return [
            'opening_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'actual_cash' => 'decimal:2',
            'cash_difference' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function terminal(): BelongsTo { return $this->belongsTo(Terminal::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function sales(): HasMany { return $this->hasMany(Sale::class); }
}
