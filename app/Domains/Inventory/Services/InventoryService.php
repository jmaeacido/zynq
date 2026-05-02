<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Branches\Models\Branch;
use App\Domains\Inventory\Models\InventoryStock;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Products\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function ensureStock(Product $product, Branch $branch, float $reorderPoint = 0): InventoryStock
    {
        if ((int) $product->tenant_id !== (int) $branch->tenant_id) {
            throw new InvalidArgumentException('Product and branch must belong to the same tenant.');
        }

        return DB::transaction(fn () => InventoryStock::firstOrCreate(
            [
                'tenant_id' => $product->tenant_id,
                'branch_id' => $branch->id,
                'product_id' => $product->id,
            ],
            ['quantity_on_hand' => 0, 'reorder_point' => $reorderPoint],
        ));
    }

    public function stockIn(Product $product, Branch $branch, float $quantity, string $reason, ?User $user = null, ?string $referenceNumber = null): StockMovement
    {
        $this->assertPositiveQuantity($quantity);

        return $this->recordMovement($product, $branch, 'stock_in', $quantity, $reason, $user, $referenceNumber);
    }

    public function stockOut(Product $product, Branch $branch, float $quantity, string $reason, ?User $user = null, ?string $referenceNumber = null): StockMovement
    {
        $this->assertPositiveQuantity($quantity);

        return $this->recordMovement($product, $branch, 'stock_out', -1 * $quantity, $reason, $user, $referenceNumber);
    }

    public function adjust(Product $product, Branch $branch, float $targetQuantity, string $reason, ?User $user = null, ?string $referenceNumber = null): StockMovement
    {
        if ($targetQuantity < 0) {
            throw new InvalidArgumentException('Target quantity cannot be negative.');
        }

        return DB::transaction(function () use ($product, $branch, $targetQuantity, $reason, $user, $referenceNumber) {
            $stock = $this->lockStock($product, $branch);
            $before = (float) $stock->quantity_on_hand;
            $delta = $targetQuantity - $before;

            return $this->createMovement($stock, 'adjustment', $delta, $before, $targetQuantity, $reason, $user, $referenceNumber);
        });
    }

    public function setReorderPoint(InventoryStock $stock, float $reorderPoint): InventoryStock
    {
        if ($reorderPoint < 0) {
            throw new InvalidArgumentException('Reorder point cannot be negative.');
        }

        return DB::transaction(function () use ($stock, $reorderPoint) {
            $stock->update(['reorder_point' => $reorderPoint]);

            return $stock->refresh();
        });
    }

    private function recordMovement(Product $product, Branch $branch, string $type, float $delta, string $reason, ?User $user, ?string $referenceNumber): StockMovement
    {
        return DB::transaction(function () use ($product, $branch, $type, $delta, $reason, $user, $referenceNumber) {
            $stock = $this->lockStock($product, $branch);
            $before = (float) $stock->quantity_on_hand;
            $after = $before + $delta;

            if ($after < 0) {
                throw new InvalidArgumentException('Stock-out quantity exceeds stock on hand.');
            }

            return $this->createMovement($stock, $type, $delta, $before, $after, $reason, $user, $referenceNumber);
        });
    }

    private function lockStock(Product $product, Branch $branch): InventoryStock
    {
        if ((int) $product->tenant_id !== (int) $branch->tenant_id) {
            throw new InvalidArgumentException('Product and branch must belong to the same tenant.');
        }

        $stock = InventoryStock::where('tenant_id', $product->tenant_id)
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            return $stock;
        }

        return InventoryStock::create([
            'tenant_id' => $product->tenant_id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 0,
            'reorder_point' => 0,
        ]);
    }

    private function createMovement(InventoryStock $stock, string $type, float $delta, float $before, float $after, string $reason, ?User $user, ?string $referenceNumber): StockMovement
    {
        $stock->update(['quantity_on_hand' => $after]);

        return StockMovement::create([
            'tenant_id' => $stock->tenant_id,
            'branch_id' => $stock->branch_id,
            'product_id' => $stock->product_id,
            'inventory_stock_id' => $stock->id,
            'user_id' => $user?->id,
            'movement_type' => $type,
            'quantity_delta' => $delta,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'reference_number' => $referenceNumber,
            'reason' => $reason,
            'metadata' => ['phase' => 2, 'audit_ready' => true],
        ]);
    }

    private function assertPositiveQuantity(float $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }
    }
}
