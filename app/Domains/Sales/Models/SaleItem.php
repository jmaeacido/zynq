<?php

namespace App\Domains\Sales\Models;

use App\Domains\Products\Models\Product;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['tenant_id', 'sale_id', 'product_id', 'sku', 'barcode', 'name', 'unit', 'tax_type', 'quantity', 'unit_price', 'line_total'])]
class SaleItem extends Model
{
    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'line_total' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => throw new LogicException('Sale items are financial records and cannot be deleted.'));
    }

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
