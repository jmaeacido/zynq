<?php

namespace Tests\Feature;

use App\Domains\Branches\Models\Branch;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Invoicing\Models\OfflineInvoiceRange;
use App\Domains\Products\Models\Product;
use App\Domains\Products\Models\ProductCategory;
use App\Domains\Sales\Models\CashSession;
use App\Domains\Sales\Models\OfflineSaleSyncRecord;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Services\CashSessionService;
use App\Domains\Sales\Services\OfflineSaleSyncService;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Terminals\Models\Terminal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_sync_endpoint_accepts_valid_offline_sale(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product, $session] = $this->fixture();
        app(InventoryService::class)->stockIn($product, $branch, 10, 'Opening stock', $cashier);

        $response = $this->actingAs($cashier)->postJson(route('sync.offline-sales.store'), $this->payload($tenant, $branch, $terminal, $cashier, $session, $product));

        $response->assertOk()->assertJson(['status' => 'synced']);
        $sale = Sale::firstOrFail();
        $this->assertSame('SI-'.$terminal->terminal_code.'-00000001', $sale->invoice_number);
        $this->assertDatabaseHas('offline_sale_sync_records', ['sale_id' => $sale->id, 'status' => 'synced']);
    }

    public function test_duplicate_idempotency_key_does_not_duplicate_sale(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product, $session] = $this->fixture();
        app(InventoryService::class)->stockIn($product, $branch, 10, 'Opening stock', $cashier);
        $payload = $this->payload($tenant, $branch, $terminal, $cashier, $session, $product);

        $this->actingAs($cashier)->postJson(route('sync.offline-sales.store'), $payload)->assertOk();
        $this->actingAs($cashier)->postJson(route('sync.offline-sales.store'), $payload)->assertOk()->assertJson(['duplicate' => true, 'status' => 'synced']);

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('offline_sale_sync_records', 1);
    }

    public function test_cross_tenant_offline_sale_is_rejected(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product, $session] = $this->fixture();
        [$otherTenant, $otherBranch, $otherTerminal] = $this->fixture('Other Tenant', '300-000-000-222');
        $payload = $this->payload($otherTenant, $otherBranch, $otherTerminal, $cashier, $session, $product);

        $this->actingAs($cashier)->postJson(route('sync.offline-sales.store'), $payload)->assertUnprocessable();
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_non_compliant_terminal_creates_conflict(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product, $session] = $this->fixture(compliantTerminal: false);
        app(InventoryService::class)->stockIn($product, $branch, 10, 'Opening stock', $cashier);

        $this->actingAs($cashier)->postJson(route('sync.offline-sales.store'), $this->payload($tenant, $branch, $terminal, $cashier, $session, $product))
            ->assertConflict()
            ->assertJson(['status' => 'conflict']);

        $this->assertDatabaseHas('offline_sale_sync_records', ['status' => 'conflict']);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_closed_cash_session_creates_conflict(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product, $session] = $this->fixture();
        app(InventoryService::class)->stockIn($product, $branch, 10, 'Opening stock', $cashier);
        app(CashSessionService::class)->close($session, 100);

        $this->actingAs($cashier)->postJson(route('sync.offline-sales.store'), $this->payload($tenant, $branch, $terminal, $cashier, $session, $product))
            ->assertConflict()
            ->assertJsonPath('conflicts.0.code', 'cash_session_closed');

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_inactive_product_creates_conflict(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product, $session] = $this->fixture();
        app(InventoryService::class)->stockIn($product, $branch, 10, 'Opening stock', $cashier);
        $product->update(['active' => false]);

        $this->actingAs($cashier)->postJson(route('sync.offline-sales.store'), $this->payload($tenant, $branch, $terminal, $cashier, $session, $product))
            ->assertConflict()
            ->assertJsonPath('conflicts.0.code', 'product_inactive');

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_stock_conflict_is_detected(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product, $session] = $this->fixture();
        app(InventoryService::class)->stockIn($product, $branch, 1, 'Opening stock', $cashier);

        $this->actingAs($cashier)->postJson(route('sync.offline-sales.store'), $this->payload($tenant, $branch, $terminal, $cashier, $session, $product, quantity: 2))
            ->assertConflict()
            ->assertJsonPath('conflicts.0.code', 'insufficient_stock');

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_successful_sync_creates_related_records_and_audit_log(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product, $session] = $this->fixture();
        app(InventoryService::class)->stockIn($product, $branch, 10, 'Opening stock', $cashier);

        $this->actingAs($cashier)->postJson(route('sync.offline-sales.store'), $this->payload($tenant, $branch, $terminal, $cashier, $session, $product))->assertOk();
        $sale = Sale::with(['items', 'payments', 'taxSummary', 'ledgerEntries'])->firstOrFail();

        $this->assertCount(1, $sale->items);
        $this->assertCount(1, $sale->payments);
        $this->assertNotNull($sale->taxSummary);
        $this->assertCount(1, $sale->ledgerEntries);
        $this->assertDatabaseHas('stock_movements', ['reference_number' => $sale->invoice_number, 'movement_type' => 'stock_out']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'offline_sale_sync_success', 'record_type' => 'sale', 'record_id' => (string) $sale->id]);
    }

    public function test_conflict_list_visible_only_to_authorized_users(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product, $session] = $this->fixture();
        $manager = $this->manager($tenant, $branch);
        app(InventoryService::class)->stockIn($product, $branch, 1, 'Opening stock', $cashier);

        $this->actingAs($cashier)->postJson(route('sync.offline-sales.store'), $this->payload($tenant, $branch, $terminal, $cashier, $session, $product, quantity: 2))->assertConflict();

        $this->actingAs($cashier)->get(route('sync.conflicts'))->assertForbidden();
        $this->actingAs($manager)->get(route('sync.conflicts'))->assertOk()->assertSee('Server stock is insufficient');
    }

    public function test_retry_conflict_does_not_duplicate_sale(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product, $session] = $this->fixture();
        $manager = $this->manager($tenant, $branch);
        app(InventoryService::class)->stockIn($product, $branch, 1, 'Opening stock', $cashier);
        $payload = $this->payload($tenant, $branch, $terminal, $cashier, $session, $product, quantity: 2);

        $this->actingAs($cashier)->postJson(route('sync.offline-sales.store'), $payload)->assertConflict();
        app(InventoryService::class)->stockIn($product, $branch, 5, 'Manager adjustment', $manager);
        $record = OfflineSaleSyncRecord::firstOrFail();

        $this->actingAs($manager)->postJson(route('sync.conflicts.retry', $record))->assertOk()->assertJson(['status' => 'synced']);
        $this->actingAs($manager)->postJson(route('sync.conflicts.retry', $record))->assertOk()->assertJson(['duplicate' => true, 'status' => 'synced']);

        $this->assertDatabaseCount('sales', 1);
    }

    public function test_cancel_conflict_prevents_future_sync(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product, $session] = $this->fixture();
        $manager = $this->manager($tenant, $branch);
        app(InventoryService::class)->stockIn($product, $branch, 1, 'Opening stock', $cashier);
        $payload = $this->payload($tenant, $branch, $terminal, $cashier, $session, $product, quantity: 2);

        $this->actingAs($cashier)->postJson(route('sync.offline-sales.store'), $payload)->assertConflict();
        $record = OfflineSaleSyncRecord::firstOrFail();

        $this->actingAs($manager)->postJson(route('sync.conflicts.cancel', $record), ['reason' => 'Duplicate customer payment'])->assertOk()->assertJson(['status' => 'cancelled']);
        app(InventoryService::class)->stockIn($product, $branch, 5, 'Manager adjustment', $manager);
        $this->actingAs($cashier)->postJson(route('sync.offline-sales.store'), $payload)->assertOk()->assertJson(['status' => 'cancelled']);

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_reviewed_conflict_logs_audit_event(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product, $session] = $this->fixture();
        $manager = $this->manager($tenant, $branch);
        app(InventoryService::class)->stockIn($product, $branch, 1, 'Opening stock', $cashier);

        $this->actingAs($cashier)->postJson(route('sync.offline-sales.store'), $this->payload($tenant, $branch, $terminal, $cashier, $session, $product, quantity: 2))->assertConflict();
        $record = OfflineSaleSyncRecord::firstOrFail();

        $this->actingAs($manager)->postJson(route('sync.conflicts.review', $record), ['reason' => 'Reviewed with cashier'])->assertOk()->assertJson(['status' => 'reviewed']);

        $this->assertDatabaseHas('audit_logs', ['action' => 'offline_sale_reviewed', 'record_id' => (string) $record->id]);
    }

    public function test_unsafe_override_is_rejected(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product, $session] = $this->fixture();
        $manager = $this->manager($tenant, $branch);
        app(InventoryService::class)->stockIn($product, $branch, 10, 'Opening stock', $cashier);
        $payload = $this->payload($tenant, $branch, $terminal, $cashier, $session, $product);
        $payload['payload_hash'] = str_repeat('a', 64);

        $this->actingAs($cashier)->postJson(route('sync.offline-sales.store'), $payload)->assertConflict();
        $record = OfflineSaleSyncRecord::firstOrFail();

        $this->actingAs($manager)->postJson(route('sync.conflicts.override', $record), ['reason' => 'Force it'])->assertUnprocessable();
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_safe_override_requires_manager_admin_reason(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product, $session] = $this->fixture();
        $manager = $this->manager($tenant, $branch);
        app(InventoryService::class)->stockIn($product, $branch, 10, 'Opening stock', $cashier);
        $payload = $this->payload($tenant, $branch, $terminal, $cashier, $session, $product);
        $product->update(['selling_price' => 24]);

        $this->actingAs($cashier)->postJson(route('sync.offline-sales.store'), $payload)->assertConflict();
        $record = OfflineSaleSyncRecord::firstOrFail();

        $this->actingAs($cashier)->postJson(route('sync.conflicts.override', $record), ['reason' => 'Cashier try'])->assertForbidden();
        $this->actingAs($manager)->postJson(route('sync.conflicts.override', $record))->assertUnprocessable();
        $this->actingAs($manager)->postJson(route('sync.conflicts.override', $record), ['reason' => 'Manager approved current server price'])->assertOk()->assertJson(['status' => 'synced']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'offline_sale_override_attempted', 'record_id' => (string) $record->id]);
    }

    public function test_offline_reference_links_to_synced_sale_and_invoice(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product, $session] = $this->fixture();
        app(InventoryService::class)->stockIn($product, $branch, 10, 'Opening stock', $cashier);
        $payload = $this->payload($tenant, $branch, $terminal, $cashier, $session, $product);

        $this->actingAs($cashier)->postJson(route('sync.offline-sales.store'), $payload)->assertOk();

        $record = OfflineSaleSyncRecord::with('sale')->firstOrFail();
        $this->assertSame($payload['offline_reference'], data_get($record->sale->metadata, 'offline_sync.offline_reference'));
        $this->assertSame('SI-'.$terminal->terminal_code.'-00000001', $record->sale->invoice_number);
    }

    public function test_reserved_range_disabled_keeps_fallback_behavior(): void
    {
        config(['offline.invoice_range_enabled' => false]);
        [$tenant, $branch, $terminal, $cashier] = $this->fixture();

        $this->actingAs($cashier)->postJson(route('sync.invoice-ranges.reserve'), [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
        ])->assertUnprocessable();

        $this->actingAs($cashier)->getJson(route('sync.invoice-ranges.status', [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
        ]))->assertOk()->assertJson(['enabled' => false, 'items' => []]);
    }

    public function test_reserved_range_reservation_rejects_overlaps(): void
    {
        config(['offline.invoice_range_enabled' => true]);
        [$tenant, $branch, $terminal, $cashier] = $this->fixture();

        $this->actingAs($cashier)->postJson(route('sync.invoice-ranges.reserve'), [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
        ])->assertOk();

        $this->actingAs($cashier)->postJson(route('sync.invoice-ranges.reserve'), [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
        ])->assertUnprocessable();
    }

    public function test_out_of_range_offline_invoice_is_rejected(): void
    {
        config(['offline.invoice_range_enabled' => true]);
        [$tenant, $branch, $terminal, $cashier, $product, $session] = $this->fixture();
        app(InventoryService::class)->stockIn($product, $branch, 10, 'Opening stock', $cashier);
        OfflineInvoiceRange::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
            'document_type' => 'sales_invoice',
            'range_start' => 1,
            'range_end' => 10,
            'next_number' => 1,
            'status' => 'active',
            'reserved_by_user_id' => $cashier->id,
            'reserved_at' => now(),
            'expires_at' => now()->addHour(),
        ]);
        $payload = $this->payload($tenant, $branch, $terminal, $cashier, $session, $product);
        $payload['sale']['offline_invoice_number'] = 99;
        $payload['payload_hash'] = app(OfflineSaleSyncService::class)->payloadHash($payload['sale']);

        $this->actingAs($cashier)->postJson(route('sync.offline-sales.store'), $payload)
            ->assertConflict()
            ->assertJsonPath('conflicts.0.code', 'offline_invoice_range_invalid');
    }

    public function test_pos_contains_stale_snapshot_and_unsupported_offline_controls(): void
    {
        [$tenant, $branch, $terminal, $cashier] = $this->fixture();

        $this->actingAs($cashier)->get(route('pos.checkout'))
            ->assertOk()
            ->assertSee('stale-snapshot-warning')
            ->assertSee('PENDING SYNC - NOT FINAL OFFICIAL INVOICE')
            ->assertSee('Sync Now');
    }

    private function payload(Tenant $tenant, Branch $branch, Terminal $terminal, User $cashier, CashSession $session, Product $product, float $quantity = 1): array
    {
        $sale = [
            'items' => [[
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => (float) $product->selling_price,
                'tax_type' => $product->tax_type,
            ]],
            'payments' => [['payment_method' => 'cash', 'amount' => 25 * $quantity, 'amount_tendered' => 25 * $quantity]],
            'discounts' => [],
        ];

        return [
            'idempotency_key' => fake()->uuid(),
            'offline_reference' => 'OFF-'.$terminal->terminal_code.'-'.fake()->unique()->numberBetween(1000, 9999),
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
            'cashier_id' => $cashier->id,
            'cash_session_id' => $session->id,
            'created_offline_at' => now()->subMinutes(5)->toIso8601String(),
            'payload_hash' => app(OfflineSaleSyncService::class)->payloadHash($sale),
            'tax_snapshot' => ['vat_rate' => 12],
            'sale' => $sale,
        ];
    }

    private function fixture(string $tenantName = 'Offline Tenant', string $tin = '300-000-000-111', bool $compliantTerminal = true): array
    {
        foreach (['create sales', 'view reports'] as $permission) {
            Permission::findOrCreate($permission);
        }
        Role::findOrCreate('Cashier')->givePermissionTo('create sales');
        Role::findOrCreate('Branch Manager')->givePermissionTo('view reports');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tenant = Tenant::create([
            'business_name' => $tenantName,
            'registered_address' => $tenantName.' Address',
            'tin' => $tin,
            'taxpayer_type' => 'VAT',
            'invoice_footer' => 'Thank you.',
        ]);
        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'branch_name' => $tenantName.' Branch',
            'branch_code' => substr(str_replace(' ', '', strtoupper($tenantName)), 0, 8),
            'address' => $tenantName.' Branch Address',
        ]);
        $terminal = Terminal::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'terminal_name' => 'POS 1',
            'terminal_code' => substr(str_replace(' ', '', strtoupper($tenantName)), 0, 3).fake()->unique()->numberBetween(10, 99),
            'machine_identification_number' => $compliantTerminal ? 'MIN-001' : null,
            'permit_to_use_number' => $compliantTerminal ? 'PTU-001' : null,
            'serial_number' => $compliantTerminal ? 'SN-001' : null,
            'software_version' => $compliantTerminal ? '13.7.0' : null,
        ]);
        $cashier = User::factory()->create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id]);
        $cashier->assignRole('Cashier');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $session = app(CashSessionService::class)->open($branch, $terminal, $cashier, 100);
        $category = ProductCategory::create(['tenant_id' => $tenant->id, 'name' => 'General', 'code' => 'GEN']);
        $product = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'sku' => 'SKU-'.$tenant->id,
            'barcode' => '48000000000'.$tenant->id,
            'name' => 'Test Product',
            'unit' => 'pcs',
            'selling_price' => 25,
            'cost_price' => 10,
            'tax_type' => 'VATABLE',
        ]);

        return [$tenant, $branch, $terminal, $cashier, $product, $session];
    }

    private function manager(Tenant $tenant, Branch $branch): User
    {
        $manager = User::factory()->create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id]);
        $manager->assignRole('Branch Manager');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $manager;
    }
}
