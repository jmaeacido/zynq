<?php

namespace App\Domains\Sales\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['tenant_id', 'sale_id', 'approved_by_user_id', 'type', 'original_invoice_number', 'amount', 'return_to_stock', 'reason', 'items', 'transaction_hash'])]
class SaleReversal extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'return_to_stock' => 'boolean', 'items' => 'array'];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => throw new LogicException('Sale reversals are append-only and cannot be deleted.'));
        static::updating(fn () => throw new LogicException('Sale reversals are append-only and cannot be updated.'));
    }

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by_user_id'); }
}
