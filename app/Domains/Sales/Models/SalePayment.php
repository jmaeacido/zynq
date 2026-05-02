<?php

namespace App\Domains\Sales\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'sale_id', 'payment_method', 'amount', 'amount_tendered', 'reference_number'])]
class SalePayment extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'amount_tendered' => 'decimal:2'];
    }

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
}
