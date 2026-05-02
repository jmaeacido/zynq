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
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

#[Fillable([
    'tenant_id', 'branch_id', 'terminal_id', 'cashier_id', 'cash_session_id', 'invoice_number', 'invoice_title', 'status',
    'subtotal', 'discount_total', 'tax_total', 'total_amount', 'amount_paid', 'change_due', 'metadata',
])]
class Sale extends Model
{
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'change_due' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => throw new LogicException('Sales are financial records and cannot be deleted.'));
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function terminal(): BelongsTo { return $this->belongsTo(Terminal::class); }
    public function cashier(): BelongsTo { return $this->belongsTo(User::class, 'cashier_id'); }
    public function cashSession(): BelongsTo { return $this->belongsTo(CashSession::class); }
    public function items(): HasMany { return $this->hasMany(SaleItem::class); }
    public function payments(): HasMany { return $this->hasMany(SalePayment::class); }
    public function discounts(): HasMany { return $this->hasMany(SaleDiscount::class); }
    public function taxSummary(): HasOne { return $this->hasOne(SaleTax::class); }
    public function reversals(): HasMany { return $this->hasMany(SaleReversal::class); }
    public function ledgerEntries(): HasMany { return $this->hasMany(FinancialLedgerEntry::class); }
}
