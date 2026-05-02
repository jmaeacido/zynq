<?php

namespace App\Domains\Sales\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'sale_id', 'approved_by_user_id', 'discount_type', 'value_type', 'value', 'amount', 'reason', 'reference_number', 'metadata'])]
class SaleDiscount extends Model
{
    protected function casts(): array
    {
        return ['value' => 'decimal:2', 'amount' => 'decimal:2', 'metadata' => 'array'];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
