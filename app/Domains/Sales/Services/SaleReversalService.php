<?php

namespace App\Domains\Sales\Services;

use App\Domains\Audit\Services\AuditService;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Models\SaleReversal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SaleReversalService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly TransactionIntegrityService $integrity,
        private readonly AuditService $audit,
    ) {
    }

    public function void(Sale $sale, User $approver, string $reason, bool $returnToStock): SaleReversal
    {
        $this->assertApprover($approver, 'approve voids');
        $this->assertSaleOpenForReversal($sale);

        if ($sale->reversals()->where('type', 'void')->exists()) {
            throw new InvalidArgumentException('Sale has already been voided.');
        }

        return $this->createReversal($sale, $approver, 'void', (float) $sale->total_amount, $reason, $returnToStock, $this->allItemQuantities($sale));
    }

    public function refund(Sale $sale, User $approver, string $reason, bool $returnToStock, ?array $items = null): SaleReversal
    {
        $this->assertApprover($approver, 'approve refunds');
        $this->assertSaleOpenForReversal($sale);

        $items = $items ? $this->normalizeRefundItems($sale, $items) : $this->allItemQuantities($sale);
        $amount = $this->refundAmount($sale, $items);
        $type = $this->isFullRefund($sale, $items) ? 'refund' : 'partial_refund';

        return $this->createReversal($sale, $approver, $type, $amount, $reason, $returnToStock, $items);
    }

    private function createReversal(Sale $sale, User $approver, string $type, float $amount, string $reason, bool $returnToStock, array $items): SaleReversal
    {
        if (blank($reason)) {
            throw new InvalidArgumentException('Reason is required.');
        }

        return DB::transaction(function () use ($sale, $approver, $type, $amount, $reason, $returnToStock, $items) {
            $payload = [
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'type' => $type,
                'amount' => round($amount, 2),
                'items' => $items,
                'return_to_stock' => $returnToStock,
            ];

            $reversal = SaleReversal::create([
                'tenant_id' => $sale->tenant_id,
                'sale_id' => $sale->id,
                'approved_by_user_id' => $approver->id,
                'type' => $type,
                'original_invoice_number' => $sale->invoice_number,
                'amount' => round($amount, 2),
                'return_to_stock' => $returnToStock,
                'reason' => $reason,
                'items' => $items,
                'transaction_hash' => $this->integrity->hash($payload),
            ]);

            $ledgerPayload = [...$payload, 'reversal_id' => $reversal->id];
            $reversal->sale->ledgerEntries()->create([
                'tenant_id' => $sale->tenant_id,
                'branch_id' => $sale->branch_id,
                'terminal_id' => $sale->terminal_id,
                'sale_reversal_id' => $reversal->id,
                'entry_type' => $type,
                'amount' => -1 * round($amount, 2),
                'reference_number' => $sale->invoice_number.'-'.$type.'-'.$reversal->id,
                'payload' => $ledgerPayload,
                'transaction_hash' => $this->integrity->hash($ledgerPayload),
            ]);

            if ($returnToStock) {
                foreach ($items as $item) {
                    $saleItem = $sale->items->firstWhere('id', $item['sale_item_id']);
                    $this->inventory->stockIn($saleItem->product, $sale->branch, (float) $item['quantity'], ucfirst(str_replace('_', ' ', $type)).' '.$sale->invoice_number, $approver, $sale->invoice_number);
                }
            }

            $this->audit->record($approver, $type, 'Sales', SaleReversal::class, $reversal->id, $reversal->toArray());

            return $reversal->refresh();
        });
    }

    private function assertApprover(User $user, string $permission): void
    {
        if (! $user->can($permission)) {
            throw new InvalidArgumentException('Manager/admin approval is required.');
        }
    }

    private function assertSaleOpenForReversal(Sale $sale): void
    {
        $sale->loadMissing(['items.product', 'branch']);
        $cashSession = $sale->cashSession()->first();

        if ($cashSession?->status !== 'open') {
            throw new InvalidArgumentException('Voids/refunds are blocked after cash session closure to preserve Z-reading reproducibility.');
        }
    }

    private function allItemQuantities(Sale $sale): array
    {
        $sale->loadMissing('items');

        return $sale->items->map(fn ($item): array => ['sale_item_id' => $item->id, 'quantity' => (float) $item->quantity])->values()->all();
    }

    private function normalizeRefundItems(Sale $sale, array $rows): array
    {
        $sale->loadMissing('items');

        return collect($rows)
            ->filter(fn ($row) => ! empty($row['sale_item_id']) && (float) ($row['quantity'] ?? 0) > 0)
            ->map(function ($row) use ($sale): array {
                $item = $sale->items->firstWhere('id', (int) $row['sale_item_id']);
                if (! $item || (float) $row['quantity'] > (float) $item->quantity) {
                    throw new InvalidArgumentException('Invalid refund quantity.');
                }

                return ['sale_item_id' => $item->id, 'quantity' => (float) $row['quantity']];
            })
            ->values()
            ->all();
    }

    private function refundAmount(Sale $sale, array $items): float
    {
        return round(collect($items)->sum(function ($row) use ($sale): float {
            $item = $sale->items->firstWhere('id', $row['sale_item_id']);

            return ((float) $item->line_total / (float) $item->quantity) * (float) $row['quantity'];
        }), 2);
    }

    private function isFullRefund(Sale $sale, array $items): bool
    {
        return count($items) === $sale->items->count()
            && collect($items)->every(fn ($row): bool => (float) $row['quantity'] === (float) $sale->items->firstWhere('id', $row['sale_item_id'])->quantity);
    }
}
