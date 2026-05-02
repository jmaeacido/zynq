<?php

namespace App\Domains\Sales\Services;

use App\Domains\Branches\Models\Branch;
use App\Domains\Discounts\Services\DiscountService;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Invoicing\Services\InvoiceNumberService;
use App\Domains\Products\Models\Product;
use App\Domains\Sales\Models\Sale;
use App\Domains\Taxation\Services\TaxEngine;
use App\Domains\Terminals\Models\Terminal;
use App\Domains\Terminals\Services\TerminalComplianceService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SaleService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly InvoiceNumberService $invoiceNumbers,
        private readonly TerminalComplianceService $terminalCompliance,
        private readonly DiscountService $discounts,
        private readonly TaxEngine $taxEngine,
        private readonly CashSessionService $cashSessions,
        private readonly TransactionIntegrityService $integrity,
    ) {
    }

    public function create(array $data, User $cashier): Sale
    {
        return DB::transaction(function () use ($data, $cashier) {
            $branch = Branch::findOrFail((int) $data['branch_id']);
            $terminal = Terminal::lockForUpdate()->findOrFail((int) $data['terminal_id']);

            $this->assertScope($cashier, $branch, $terminal);
            $this->assertTerminalReady($terminal);
            $cashSession = $this->cashSessions->openSessionForSale($branch, $terminal, $cashier);

            if (! $cashSession) {
                throw new InvalidArgumentException('Cashier must open a cash session before creating sales.');
            }

            $items = $this->normalizeItems($data['items'], $branch);
            $grossTotal = round(array_sum(array_column($items, 'line_total')), 2);
            $discounts = $this->discounts->calculate($data['discounts'] ?? [], $grossTotal);
            $taxSummary = $this->taxEngine->summarize($branch->tenant, $items, $discounts['total']);
            $payments = $this->normalizePayments($data['payments'], $taxSummary['total_amount_due']);

            $sale = Sale::create([
                'tenant_id' => $branch->tenant_id,
                'branch_id' => $branch->id,
                'terminal_id' => $terminal->id,
                'cashier_id' => $cashier->id,
                'cash_session_id' => $cashSession->id,
                'invoice_number' => $this->invoiceNumbers->next($terminal),
                'invoice_title' => 'Sales Invoice',
                'status' => 'completed',
                'subtotal' => $taxSummary['gross_sales'],
                'discount_total' => $taxSummary['discounts'],
                'tax_total' => $taxSummary['vat_amount'],
                'total_amount' => $taxSummary['total_amount_due'],
                'amount_paid' => $payments['amount_paid'],
                'change_due' => $payments['change_due'],
                'metadata' => ['phase' => 4],
            ]);

            foreach ($discounts['rows'] as $discount) {
                $sale->discounts()->create(['tenant_id' => $sale->tenant_id, ...$discount]);
            }

            $sale->taxSummary()->create([
                'tenant_id' => $sale->tenant_id,
                ...$taxSummary,
                'metadata' => ['phase' => 4, 'taxpayer_type' => $branch->tenant->taxpayer_type],
            ]);

            foreach ($items as $item) {
                $sale->items()->create([
                    'tenant_id' => $sale->tenant_id,
                    'product_id' => $item['product']->id,
                    'sku' => $item['product']->sku,
                    'barcode' => $item['product']->barcode,
                    'name' => $item['product']->name,
                    'unit' => $item['product']->unit,
                    'tax_type' => $item['product']->tax_type,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                ]);

                $this->inventory->stockOut(
                    $item['product'],
                    $branch,
                    $item['quantity'],
                    'Sale '.$sale->invoice_number,
                    $cashier,
                    $sale->invoice_number,
                );
            }

            foreach ($payments['rows'] as $payment) {
                $sale->payments()->create([
                    'tenant_id' => $sale->tenant_id,
                    'payment_method' => $payment['payment_method'],
                    'amount' => $payment['amount'],
                    'amount_tendered' => $payment['amount_tendered'],
                    'reference_number' => $payment['reference_number'],
                ]);
            }

            $payload = [
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'total_amount' => (float) $sale->total_amount,
                'status' => $sale->status,
            ];
            $sale->ledgerEntries()->create([
                'tenant_id' => $sale->tenant_id,
                'branch_id' => $sale->branch_id,
                'terminal_id' => $sale->terminal_id,
                'entry_type' => 'sale',
                'amount' => $sale->total_amount,
                'reference_number' => $sale->invoice_number,
                'payload' => $payload,
                'transaction_hash' => $this->integrity->hash($payload),
            ]);

            return $sale->load(['tenant', 'branch', 'terminal', 'cashier', 'items', 'payments', 'discounts', 'taxSummary', 'ledgerEntries']);
        });
    }

    private function assertScope(User $cashier, Branch $branch, Terminal $terminal): void
    {
        if ((int) $terminal->tenant_id !== (int) $branch->tenant_id || (int) $terminal->branch_id !== (int) $branch->id) {
            throw new InvalidArgumentException('Terminal must belong to the selected branch and tenant.');
        }

        if (! $cashier->hasRole('Super Admin') && (int) $cashier->tenant_id !== (int) $branch->tenant_id) {
            throw new InvalidArgumentException('Cashier cannot sell for another tenant.');
        }

        if (! $cashier->hasRole('Super Admin') && $cashier->branch_id && (int) $cashier->branch_id !== (int) $branch->id) {
            throw new InvalidArgumentException('Cashier cannot sell for another branch.');
        }
    }

    private function assertTerminalReady(Terminal $terminal): void
    {
        if (! $this->terminalCompliance->isSaleReady($terminal)) {
            throw new InvalidArgumentException('Terminal compliance setup is incomplete: '.implode(', ', $this->terminalCompliance->missingRequirements($terminal)));
        }
    }

    private function normalizeItems(array $rows, Branch $branch): array
    {
        $items = [];

        foreach ($rows as $row) {
            if (empty($row['product_id'])) {
                continue;
            }

            $product = Product::where('tenant_id', $branch->tenant_id)->where('active', true)->find((int) $row['product_id']);

            if (! $product) {
                throw new InvalidArgumentException('A selected product is inactive or does not belong to this tenant.');
            }

            $quantity = (float) $row['quantity'];
            if ($quantity <= 0) {
                throw new InvalidArgumentException('Item quantity must be greater than zero.');
            }

            $unitPrice = round((float) $product->selling_price, 2);
            $items[] = [
                'product' => $product,
                'tax_type' => $product->tax_type,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => round($quantity * $unitPrice, 2),
            ];
        }

        if ($items === []) {
            throw new InvalidArgumentException('Sale must contain at least one product.');
        }

        return $items;
    }

    private function normalizePayments(array $rows, float $total): array
    {
        $payments = [];
        $amountPaid = 0.0;
        $cashChange = 0.0;

        foreach ($rows as $row) {
            if (empty($row['payment_method']) || (float) ($row['amount'] ?? 0) <= 0) {
                continue;
            }

            $method = $row['payment_method'];
            if (! in_array($method, ['cash', 'card', 'e_wallet'], true)) {
                throw new InvalidArgumentException('Unsupported payment method.');
            }

            $amount = round((float) $row['amount'], 2);
            $tendered = isset($row['amount_tendered']) && $row['amount_tendered'] !== null && $row['amount_tendered'] !== ''
                ? round((float) $row['amount_tendered'], 2)
                : null;

            if ($method === 'cash') {
                $cashChange += max(0, ($tendered ?? $amount) - $amount);
            }

            $amountPaid += $amount;
            $payments[] = [
                'payment_method' => $method,
                'amount' => $amount,
                'amount_tendered' => $tendered,
                'reference_number' => $row['reference_number'] ?? null,
            ];
        }

        if ($payments === []) {
            throw new InvalidArgumentException('Sale must have at least one payment.');
        }

        if (round($amountPaid, 2) < round($total, 2)) {
            throw new InvalidArgumentException('Payment amount is less than total amount due.');
        }

        return [
            'rows' => $payments,
            'amount_paid' => round($amountPaid, 2),
            'change_due' => round($cashChange + max(0, $amountPaid - $total), 2),
        ];
    }
}
