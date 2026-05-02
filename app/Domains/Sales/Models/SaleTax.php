<?php

namespace App\Domains\Sales\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'sale_id', 'vat_rate', 'gross_sales', 'vatable_sales', 'vat_amount', 'vat_exempt_sales', 'zero_rated_sales', 'non_vat_sales', 'discounts', 'net_sales', 'total_amount_due', 'metadata'])]
class SaleTax extends Model
{
    protected function casts(): array
    {
        return [
            'vat_rate' => 'decimal:4',
            'gross_sales' => 'decimal:2',
            'vatable_sales' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'vat_exempt_sales' => 'decimal:2',
            'zero_rated_sales' => 'decimal:2',
            'non_vat_sales' => 'decimal:2',
            'discounts' => 'decimal:2',
            'net_sales' => 'decimal:2',
            'total_amount_due' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
