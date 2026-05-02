<?php

namespace App\Domains\Sales\Services;

use App\Domains\Audit\Services\AuditService;
use App\Domains\Branches\Models\Branch;
use App\Domains\Inventory\Models\InventoryStock;
use App\Domains\Products\Models\Product;
use App\Domains\Sales\Models\CashSession;
use App\Domains\Sales\Models\OfflineSaleSyncRecord;
use App\Domains\Settings\Models\TenantSetting;
use App\Domains\Taxation\Services\TaxEngine;
use App\Domains\Terminals\Models\Terminal;
use App\Domains\Terminals\Services\TerminalComplianceService;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OfflineSaleSyncService
{
    public function __construct(
        private readonly SaleService $sales,
        private readonly TerminalComplianceService $terminalCompliance,
        private readonly TaxEngine $taxEngine,
        private readonly AuditService $audit,
    ) {
    }

    public function snapshot(User $cashier, Branch $branch, Terminal $terminal): array
    {
        $this->assertScope($cashier, $branch, $terminal);

        $cashSession = CashSession::where('tenant_id', $branch->tenant_id)
            ->where('branch_id', $branch->id)
            ->where('terminal_id', $terminal->id)
            ->where('user_id', $cashier->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        $products = Product::where('tenant_id', $branch->tenant_id)
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($branch): array {
                $stock = InventoryStock::where('tenant_id', $product->tenant_id)
                    ->where('branch_id', $branch->id)
                    ->where('product_id', $product->id)
                    ->first();

                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'name' => $product->name,
                    'unit' => $product->unit,
                    'price' => (float) $product->selling_price,
                    'tax_type' => $product->tax_type,
                    'stock_estimate' => (float) ($stock?->quantity_on_hand ?? 0),
                    'updated_at' => $product->updated_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        $vatSetting = TenantSetting::where('tenant_id', $branch->tenant_id)->where('key', 'vat_rate')->first();

        $payload = [
            'downloaded_at' => now()->toIso8601String(),
            'tenant' => [
                'id' => $branch->tenant->id,
                'business_name' => $branch->tenant->business_name,
                'trade_name' => $branch->tenant->trade_name,
                'tin' => $branch->tenant->tin,
                'taxpayer_type' => $branch->tenant->taxpayer_type,
                'registered_address' => $branch->tenant->registered_address,
                'invoice_footer' => $branch->tenant->invoice_footer,
            ],
            'branch' => [
                'id' => $branch->id,
                'branch_name' => $branch->branch_name,
                'branch_code' => $branch->branch_code,
                'address' => $branch->address,
                'bir_registered_address' => $branch->bir_registered_address,
            ],
            'terminal' => [
                'id' => $terminal->id,
                'terminal_name' => $terminal->terminal_name,
                'terminal_code' => $terminal->terminal_code,
                'machine_identification_number' => $terminal->machine_identification_number,
                'serial_number' => $terminal->serial_number,
                'permit_to_use_number' => $terminal->permit_to_use_number,
                'accreditation_number' => $terminal->accreditation_number,
                'software_version' => $terminal->software_version,
                'compliant' => $this->terminalCompliance->isSaleReady($terminal),
                'missing_requirements' => $this->terminalCompliance->missingRequirements($terminal),
            ],
            'cashier' => [
                'id' => $cashier->id,
                'name' => $cashier->name,
                'email' => $cashier->email,
            ],
            'cash_session' => $cashSession ? [
                'id' => $cashSession->id,
                'opened_at' => $cashSession->opened_at?->toIso8601String(),
                'opening_cash' => (float) $cashSession->opening_cash,
                'status' => $cashSession->status,
            ] : null,
            'products' => $products,
            'tax_types' => ['VATABLE', 'VAT_EXEMPT', 'ZERO_RATED', 'NON_VAT'],
            'vat_settings' => [
                'rate' => $this->taxEngine->vatRateForTenant($branch->tenant),
                'updated_at' => $vatSetting?->updated_at?->toIso8601String(),
            ],
            'invoice_strategy' => [
                'mode' => 'temporary_reference_until_sync',
                'message' => 'Offline receipts are pending sync and receive official invoice numbers only after server sync.',
            ],
        ];

        $this->audit->record($cashier, 'offline_snapshot_download', 'offline_sync', 'terminal', $terminal->id, [
            'branch_id' => $branch->id,
            'product_count' => count($products),
            'has_open_cash_session' => (bool) $cashSession,
        ], tenantId: $branch->tenant_id, branchId: $branch->id);

        return $payload;
    }

    public function sync(User $cashier, array $payload): array
    {
        return DB::transaction(function () use ($cashier, $payload): array {
            $tenantId = (int) $payload['tenant_id'];
            $idempotencyKey = (string) $payload['idempotency_key'];
            $existing = OfflineSaleSyncRecord::where('tenant_id', $tenantId)
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if (! $cashier->hasRole('Super Admin') && ((int) $cashier->tenant_id !== (int) $existing->tenant_id || ($cashier->branch_id && (int) $cashier->branch_id !== (int) $existing->branch_id))) {
                    throw new InvalidArgumentException('User cannot sync offline sales for another tenant.');
                }

                $this->audit->record($cashier, 'offline_sale_sync_retry', 'offline_sync', 'offline_sale_sync_record', $existing->id, [
                    'status' => $existing->status,
                    'sale_id' => $existing->sale_id,
                ], tenantId: $existing->tenant_id, branchId: $existing->branch_id);

                return $this->responseFor($existing, duplicate: true);
            }

            $branch = Branch::with('tenant')->findOrFail((int) $payload['branch_id']);
            $terminal = Terminal::lockForUpdate()->findOrFail((int) $payload['terminal_id']);
            $this->assertScope($cashier, $branch, $terminal);

            $record = OfflineSaleSyncRecord::create([
                'tenant_id' => $branch->tenant_id,
                'branch_id' => $branch->id,
                'terminal_id' => $terminal->id,
                'cashier_id' => (int) $payload['cashier_id'],
                'cash_session_id' => $payload['cash_session_id'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'offline_reference' => (string) $payload['offline_reference'],
                'payload_hash' => (string) $payload['payload_hash'],
                'status' => 'pending',
                'created_offline_at' => $payload['created_offline_at'],
                'payload' => $payload,
            ]);

            $conflicts = $this->conflicts($payload, $cashier, $branch, $terminal);
            if ($conflicts !== []) {
                $record->update(['status' => 'conflict', 'conflicts' => $conflicts]);
                $this->audit->record($cashier, 'offline_sale_conflict', 'offline_sync', 'offline_sale_sync_record', $record->id, [
                    'offline_reference' => $record->offline_reference,
                    'conflicts' => $conflicts,
                ], tenantId: $record->tenant_id, branchId: $record->branch_id);

                return $this->responseFor($record->refresh());
            }

            try {
                $sale = $this->sales->create([
                    'branch_id' => $branch->id,
                    'terminal_id' => $terminal->id,
                    'items' => Arr::get($payload, 'sale.items', []),
                    'payments' => Arr::get($payload, 'sale.payments', []),
                    'discounts' => Arr::get($payload, 'sale.discounts', []),
                ], $cashier);
            } catch (InvalidArgumentException $exception) {
                $record->update(['status' => 'conflict', 'conflicts' => [['code' => 'sale_service_rejected', 'message' => $exception->getMessage()]]]);
                $this->audit->record($cashier, 'offline_sale_conflict', 'offline_sync', 'offline_sale_sync_record', $record->id, [
                    'offline_reference' => $record->offline_reference,
                    'message' => $exception->getMessage(),
                ], tenantId: $record->tenant_id, branchId: $record->branch_id);

                return $this->responseFor($record->refresh());
            } catch (\Throwable $exception) {
                $record->update(['status' => 'failed', 'last_error' => $exception->getMessage()]);
                $this->audit->record($cashier, 'offline_sale_sync_failure', 'offline_sync', 'offline_sale_sync_record', $record->id, [
                    'offline_reference' => $record->offline_reference,
                    'message' => $exception->getMessage(),
                ], tenantId: $record->tenant_id, branchId: $record->branch_id);

                throw $exception;
            }

            $metadata = $sale->metadata ?? [];
            $metadata['offline_sync'] = [
                'idempotency_key' => $idempotencyKey,
                'offline_reference' => $record->offline_reference,
                'created_offline_at' => $record->created_offline_at?->toIso8601String(),
                'payload_hash' => $record->payload_hash,
            ];
            $sale->update(['metadata' => $metadata]);

            $record->update(['status' => 'synced', 'sale_id' => $sale->id, 'synced_at' => now()]);
            $this->audit->record($cashier, 'offline_sale_sync_success', 'offline_sync', 'sale', $sale->id, [
                'offline_reference' => $record->offline_reference,
                'invoice_number' => $sale->invoice_number,
            ], tenantId: $record->tenant_id, branchId: $record->branch_id);

            return $this->responseFor($record->refresh());
        });
    }

    private function conflicts(array $payload, User $cashier, Branch $branch, Terminal $terminal): array
    {
        $conflicts = [];

        if ((int) $payload['tenant_id'] !== (int) $branch->tenant_id) {
            $conflicts[] = ['code' => 'tenant_mismatch', 'message' => 'Offline sale tenant does not match the selected branch.'];
        }

        if ((int) $payload['cashier_id'] !== (int) $cashier->id) {
            $conflicts[] = ['code' => 'cashier_mismatch', 'message' => 'Offline sale cashier does not match the authenticated user.'];
        }

        if (! hash_equals($this->payloadHash($payload['sale'] ?? []), (string) $payload['payload_hash'])) {
            $conflicts[] = ['code' => 'payload_hash_mismatch', 'message' => 'Offline sale payload hash does not match the submitted sale data.'];
        }

        if (! $this->terminalCompliance->isSaleReady($terminal)) {
            $conflicts[] = ['code' => 'terminal_not_compliant', 'message' => 'Terminal compliance setup is incomplete.'];
        }

        $cashSession = CashSession::where('tenant_id', $branch->tenant_id)
            ->where('branch_id', $branch->id)
            ->where('terminal_id', $terminal->id)
            ->where('user_id', $cashier->id)
            ->where('id', (int) ($payload['cash_session_id'] ?? 0))
            ->first();

        if (! $cashSession || $cashSession->status !== 'open') {
            $conflicts[] = ['code' => 'cash_session_closed', 'message' => 'The cached cash session is no longer open on the server.'];
        }

        foreach (Arr::get($payload, 'sale.items', []) as $item) {
            $product = Product::where('tenant_id', $branch->tenant_id)->find((int) ($item['product_id'] ?? 0));
            if (! $product || ! $product->active) {
                $conflicts[] = ['code' => 'product_inactive', 'message' => 'A product in the offline sale is inactive or missing.', 'product_id' => $item['product_id'] ?? null];
                continue;
            }

            if (round((float) $product->selling_price, 2) !== round((float) ($item['unit_price'] ?? 0), 2)) {
                $conflicts[] = ['code' => 'price_changed', 'message' => 'A product price changed after the offline snapshot.', 'product_id' => $product->id];
            }

            if ($product->tax_type !== ($item['tax_type'] ?? $product->tax_type)) {
                $conflicts[] = ['code' => 'tax_settings_changed', 'message' => 'A product tax type changed after the offline snapshot.', 'product_id' => $product->id];
            }

            $stock = InventoryStock::where('tenant_id', $branch->tenant_id)
                ->where('branch_id', $branch->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();
            if ((float) ($stock?->quantity_on_hand ?? 0) < (float) ($item['quantity'] ?? 0)) {
                $conflicts[] = ['code' => 'insufficient_stock', 'message' => 'Server stock is insufficient for an offline sale item.', 'product_id' => $product->id];
            }
        }

        if (isset($payload['tax_snapshot']['vat_rate']) && round((float) $payload['tax_snapshot']['vat_rate'], 4) !== round($this->taxEngine->vatRateForTenant($branch->tenant), 4)) {
            $conflicts[] = ['code' => 'tax_settings_changed', 'message' => 'VAT settings changed after the offline snapshot.'];
        }

        return $conflicts;
    }

    private function responseFor(OfflineSaleSyncRecord $record, bool $duplicate = false): array
    {
        return [
            'status' => $record->status,
            'duplicate' => $duplicate,
            'offline_reference' => $record->offline_reference,
            'idempotency_key' => $record->idempotency_key,
            'sale_id' => $record->sale_id,
            'invoice_number' => $record->sale?->invoice_number,
            'conflicts' => $record->conflicts ?? [],
            'message' => match ($record->status) {
                'synced' => 'Offline sale synced successfully.',
                'conflict' => 'Offline sale needs manager/admin review before it can be finalized.',
                'failed' => 'Offline sale sync failed and can be retried.',
                default => 'Offline sale is pending sync.',
            },
        ];
    }

    private function assertScope(User $cashier, Branch $branch, Terminal $terminal): void
    {
        if ((int) $terminal->tenant_id !== (int) $branch->tenant_id || (int) $terminal->branch_id !== (int) $branch->id) {
            throw new InvalidArgumentException('Terminal must belong to the selected branch and tenant.');
        }

        if (! $cashier->hasRole('Super Admin') && (int) $cashier->tenant_id !== (int) $branch->tenant_id) {
            throw new InvalidArgumentException('User cannot sync offline sales for another tenant.');
        }

        if (! $cashier->hasRole('Super Admin') && $cashier->branch_id && (int) $cashier->branch_id !== (int) $branch->id) {
            throw new InvalidArgumentException('User cannot sync offline sales for another branch.');
        }
    }

    public function payloadHash(array $salePayload): string
    {
        return hash('sha256', json_encode($this->stable($salePayload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function stable(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->stable($item), $value);
        }

        ksort($value);

        return array_map(fn (mixed $item): mixed => $this->stable($item), $value);
    }
}
