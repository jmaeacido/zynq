<?php

namespace App\Domains\Sales\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['tenant_id', 'branch_id', 'terminal_id', 'sale_id', 'sale_reversal_id', 'entry_type', 'amount', 'reference_number', 'payload', 'transaction_hash'])]
class FinancialLedgerEntry extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payload' => 'array'];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => throw new LogicException('Ledger entries are append-only and cannot be deleted.'));
        static::updating(fn () => throw new LogicException('Ledger entries are append-only and cannot be updated.'));
    }

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function reversal(): BelongsTo { return $this->belongsTo(SaleReversal::class, 'sale_reversal_id'); }
}
