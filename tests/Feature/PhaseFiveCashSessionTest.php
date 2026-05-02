<?php

namespace Tests\Feature;

use App\Domains\Branches\Models\Branch;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Products\Models\Product;
use App\Domains\Products\Models\ProductCategory;
use App\Domains\Reports\Services\CashReadingReportService;
use App\Domains\Sales\Models\CashSession;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Services\CashSessionService;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Terminals\Models\Terminal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PhaseFiveCashSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_open_and_close_cash_session(): void
    {
        [$tenant, $branch, $terminal, $cashier] = $this->fixture(withProduct: false);

        $this->actingAs($cashier)->post(route('cash-sessions.store'), [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
            'opening_cash' => 500,
        ])->assertRedirect();

        $session = CashSession::firstOrFail();
        $this->assertSame('open', $session->status);

        $this->actingAs($cashier)->post(route('cash-sessions.close', $session), [
            'actual_cash' => 500,
        ])->assertRedirect(route('cash-sessions.show', $session));

        $session->refresh();
        $this->assertSame('closed', $session->status);
        $this->assertEquals(500, (float) $session->expected_cash);
        $this->assertEquals(0, (float) $session->cash_difference);
    }

    public function test_sale_requires_open_cash_session(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product] = $this->fixture();
        (new InventoryService())->stockIn($product, $branch, 10, 'Opening stock', $cashier);

        $this->actingAs($cashier)->post(route('pos.sales.store'), $this->salePayload($branch, $terminal, $product))
            ->assertSessionHasErrors('sale');

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_duplicate_open_session_is_blocked(): void
    {
        [$tenant, $branch, $terminal, $cashier] = $this->fixture(withProduct: false);
        $service = new CashSessionService();
        $service->open($branch, $terminal, $cashier, 100);

        $this->expectException(\InvalidArgumentException::class);
        $service->open($branch, $terminal, $cashier, 100);
    }

    public function test_x_and_z_totals_match_completed_sales_under_session(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product] = $this->fixture();
        $service = new CashSessionService();
        $session = $service->open($branch, $terminal, $cashier, 100);
        (new InventoryService())->stockIn($product, $branch, 10, 'Opening stock', $cashier);

        $this->actingAs($cashier)->post(route('pos.sales.store'), $this->salePayload($branch, $terminal, $product, cashAmount: 25));
        $this->actingAs($cashier)->post(route('pos.sales.store'), $this->salePayload($branch, $terminal, $product, cashAmount: 25));

        $reading = (new CashReadingReportService())->reading($session->refresh());
        $this->assertEquals(2, $reading['sale_count']);
        $this->assertEquals(50, $reading['net_sales']);
        $this->assertEquals(150, $reading['expected_cash']);

        $closed = $service->close($session, 150);
        $z = (new CashReadingReportService())->reading($closed);
        $this->assertTrue($z['z_valid']);
        $this->assertEquals(150, $z['actual_cash']);
        $this->assertEquals(0, $z['cash_difference']);
    }

    public function test_close_validation_prevents_closing_twice(): void
    {
        [$tenant, $branch, $terminal, $cashier] = $this->fixture(withProduct: false);
        $service = new CashSessionService();
        $session = $service->open($branch, $terminal, $cashier, 100);
        $service->close($session, 100);

        $this->expectException(\InvalidArgumentException::class);
        $service->close($session, 100);
    }

    private function salePayload(Branch $branch, Terminal $terminal, Product $product, float $cashAmount = 25): array
    {
        return [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'cash', 'amount' => $cashAmount, 'amount_tendered' => $cashAmount]],
        ];
    }

    private function fixture(bool $withProduct = true): array
    {
        foreach (['create sales', 'view reports'] as $permission) {
            Permission::findOrCreate($permission);
        }
        Role::findOrCreate('Cashier')->givePermissionTo('create sales');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tenant = Tenant::create([
            'business_name' => 'Phase 5 Tenant',
            'registered_address' => 'Address',
            'tin' => '500-000-000-001',
            'taxpayer_type' => 'VAT',
        ]);
        $branch = Branch::create(['tenant_id' => $tenant->id, 'branch_name' => 'Main', 'branch_code' => 'P5', 'address' => 'Branch']);
        $terminal = Terminal::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'terminal_name' => 'POS',
            'terminal_code' => 'P501',
            'machine_identification_number' => 'MIN',
            'permit_to_use_number' => 'PTU',
            'serial_number' => 'SN',
            'software_version' => '13.7.0',
        ]);
        $cashier = User::factory()->create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id]);
        $cashier->assignRole('Cashier');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (! $withProduct) {
            return [$tenant, $branch, $terminal, $cashier];
        }

        $category = ProductCategory::create(['tenant_id' => $tenant->id, 'name' => 'General', 'code' => 'GEN']);
        $product = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'sku' => 'P5-001',
            'name' => 'Phase 5 Product',
            'unit' => 'pcs',
            'selling_price' => 25,
            'cost_price' => 10,
            'tax_type' => 'VATABLE',
        ]);

        return [$tenant, $branch, $terminal, $cashier, $product];
    }
}
