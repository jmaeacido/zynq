<?php

namespace App\Domains\Products\Models;

use App\Domains\Inventory\Models\InventoryStock;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'tenant_id',
    'category_id',
    'sku',
    'barcode',
    'name',
    'description',
    'unit',
    'selling_price',
    'cost_price',
    'tax_type',
    'active',
    'archived_at',
])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'archived_at' => 'datetime',
            'selling_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
