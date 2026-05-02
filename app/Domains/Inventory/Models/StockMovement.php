<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Branches\Models\Branch;
use App\Domains\Products\Models\Product;
use App\Domains\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'tenant_id',
    'branch_id',
    'product_id',
    'inventory_stock_id',
    'user_id',
    'movement_type',
    'quantity_delta',
    'quantity_before',
    'quantity_after',
    'reference_number',
    'reason',
    'metadata',
])]
class StockMovement extends Model
{
    protected function casts(): array
    {
        return [
            'quantity_delta' => 'decimal:3',
            'quantity_before' => 'decimal:3',
            'quantity_after' => 'decimal:3',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new LogicException('Stock movements are append-only and cannot be deleted.');
        });

        static::updating(function (): never {
            throw new LogicException('Stock movements are append-only and cannot be updated.');
        });
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

    public function stock(): BelongsTo
    {
        return $this->belongsTo(InventoryStock::class, 'inventory_stock_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
