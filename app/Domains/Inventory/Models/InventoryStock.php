<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Branches\Models\Branch;
use App\Domains\Products\Models\Product;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tenant_id', 'branch_id', 'product_id', 'quantity_on_hand', 'reorder_point'])]
class InventoryStock extends Model
{
    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:3',
            'reorder_point' => 'decimal:3',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity_on_hand', '<=', 'reorder_point');
    }

    public function isLowStock(): bool
    {
        return (float) $this->quantity_on_hand <= (float) $this->reorder_point;
    }
}
