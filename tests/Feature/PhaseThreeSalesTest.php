<?php

namespace Tests\Feature;

use App\Domains\Branches\Models\Branch;
use App\Domains\Inventory\Models\InventoryStock;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Products\Models\Product;
use App\Domains\Products\Models\ProductCategory;
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

class PhaseThreeSalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_creation_deducts_stock_and_generates_invoice_number(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product] = $this->saleFixture();
        (new InventoryService())->stockIn($product, $branch, 10, 'Opening stock', $cashier);

        $response = $this->actingAs($cashier)->post(route('pos.sales.store'), [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'payments' => [
                ['payment_method' => 'cash', 'amount' => 50, 'amount_tendered' => 100],
            ],
        ]);

        $sale = Sale::firstOrFail();
        $response->assertRedirect(route('sales.show', $sale));
        $this->assertSame('SI-'.$terminal->terminal_code.'-00000001', $sale->invoice_number);
        $this->assertSame('completed', $sale->status);
        $this->assertEquals(50.00, (float) $sale->total_amount);
        $this->assertEquals(50.00, (float) $sale->change_due);
        $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id, 'product_id' => $product->id, 'quantity' => 2]);
        $this->assertDatabaseHas('inventory_stocks', ['product_id' => $product->id, 'quantity_on_hand' => 8]);
        $this->assertDatabaseHas('stock_movements', ['movement_type' => 'stock_out', 'reference_number' => $sale->invoice_number, 'quantity_after' => 8]);
    }

    public function test_pos_checkout_page_renders(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product] = $this->saleFixture();

        $this->actingAs($cashier)->get(route('pos.checkout'))
            ->assertOk()
            ->assertSee('POS Checkout')
            ->assertSee('Shortcuts:')
            ->assertSee('Ready to scan.');
    }

    public function test_pos_product_search_prioritizes_exact_barcode_and_sku(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product] = $this->saleFixture();
        $category = ProductCategory::firstOrFail();
        Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'sku' => 'ALT-SKU',
            'barcode' => 'ALT-BARCODE',
            'name' => '48000000000'.$tenant->id.' Similar Name',
            'unit' => 'pcs',
            'selling_price' => 10,
            'cost_price' => 5,
            'tax_type' => 'VATABLE',
        ]);

        $this->actingAs($cashier)->getJson(route('pos.products', ['q' => $product->barcode]))
            ->assertOk()
            ->assertJsonPath('0.id', $product->id);

        $this->actingAs($cashier)->getJson(route('pos.products', ['q' => str_replace('-', '', $product->sku)]))
            ->assertOk()
            ->assertJsonPath('0.id', $product->id);
    }

    public function test_json_sale_rejects_insufficient_payment(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product] = $this->saleFixture();
        (new InventoryService())->stockIn($product, $branch, 10, 'Opening stock', $cashier);

        $this->actingAs($cashier)->postJson(route('pos.sales.store'), [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'payments' => [
                ['payment_method' => 'cash', 'amount' => 10, 'amount_tendered' => 10],
            ],
        ])->assertUnprocessable()->assertJson(['message' => 'Payment amount is less than total amount due.']);

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_json_sale_returns_invoice_actions_for_pos_reset_flow(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product] = $this->saleFixture();
        (new InventoryService())->stockIn($product, $branch, 10, 'Opening stock', $cashier);

        $response = $this->actingAs($cashier)->postJson(route('pos.sales.store'), [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'payments' => [
                ['payment_method' => 'cash', 'amount' => 50, 'amount_tendered' => 100],
            ],
        ])->assertOk()
            ->assertJsonPath('message', 'Sale completed.')
            ->assertJsonStructure(['sale_id', 'invoice_number', 'show_url', 'thermal_invoice_url', 'a4_invoice_url']);

        $sale = Sale::firstOrFail();
        $response->assertJsonPath('thermal_invoice_url', route('sales.invoice.thermal', $sale));
        $response->assertJsonPath('a4_invoice_url', route('sales.invoice.a4', $sale));
    }

    public function test_invoice_numbering_is_sequential_per_terminal(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product] = $this->saleFixture();
        (new InventoryService())->stockIn($product, $branch, 10, 'Opening stock', $cashier);

        $payload = [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'cash', 'amount' => 25, 'amount_tendered' => 25]],
        ];

        $this->actingAs($cashier)->post(route('pos.sales.store'), $payload);
        $this->actingAs($cashier)->post(route('pos.sales.store'), $payload);

        $this->assertSame([
            'SI-'.$terminal->terminal_code.'-00000001',
            'SI-'.$terminal->terminal_code.'-00000002',
        ], Sale::orderBy('id')->pluck('invoice_number')->all());
    }

    public function test_terminal_compliance_blocks_sale_creation(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product] = $this->saleFixture(compliantTerminal: false);
        (new InventoryService())->stockIn($product, $branch, 10, 'Opening stock', $cashier);

        $this->actingAs($cashier)->post(route('pos.sales.store'), [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'cash', 'amount' => 25, 'amount_tendered' => 25]],
        ])->assertSessionHasErrors('sale');

        $this->assertDatabaseCount('sales', 0);
        $this->assertEquals(10, (float) InventoryStock::first()->quantity_on_hand);
    }

    public function test_cross_tenant_products_cannot_be_sold(): void
    {
        [$tenant, $branch, $terminal, $cashier] = $this->saleFixture(withProduct: false);
        [$otherTenant, $otherBranch, $otherTerminal, $otherCashier, $otherProduct] = $this->saleFixture('Other Tenant', '300-000-000-002');

        $this->actingAs($cashier)->post(route('pos.sales.store'), [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
            'items' => [['product_id' => $otherProduct->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'cash', 'amount' => 25, 'amount_tendered' => 25]],
        ])->assertSessionHasErrors('sale');

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_invoice_pages_render_for_completed_sale(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product] = $this->saleFixture();
        (new InventoryService())->stockIn($product, $branch, 10, 'Opening stock', $cashier);
        $this->actingAs($cashier)->post(route('pos.sales.store'), [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'cash', 'amount' => 25, 'amount_tendered' => 25]],
        ]);
        $sale = Sale::firstOrFail();

        $this->actingAs($cashier)->get(route('sales.invoice.thermal', $sale))->assertOk()->assertSee($sale->invoice_number);
        $this->actingAs($cashier)->get(route('sales.invoice.a4', $sale))->assertOk()->assertSee($sale->invoice_number);
    }

    private function saleFixture(string $tenantName = 'Sale Tenant', string $tin = '300-000-000-001', bool $compliantTerminal = true, bool $withProduct = true): array
    {
        foreach (['create sales', 'view reports'] as $permission) {
            Permission::findOrCreate($permission);
        }
        Role::findOrCreate('Cashier')->givePermissionTo('create sales');
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
            'terminal_code' => substr(str_replace(' ', '', strtoupper($tenantName)), 0, 3).'01',
            'machine_identification_number' => $compliantTerminal ? 'MIN-001' : null,
            'permit_to_use_number' => $compliantTerminal ? 'PTU-001' : null,
            'serial_number' => $compliantTerminal ? 'SN-001' : null,
            'software_version' => $compliantTerminal ? '13.7.0' : null,
        ]);
        $cashier = User::factory()->create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id]);
        $cashier->assignRole('Cashier');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        (new CashSessionService())->open($branch, $terminal, $cashier, 100);

        if (! $withProduct) {
            return [$tenant, $branch, $terminal, $cashier];
        }

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

        return [$tenant, $branch, $terminal, $cashier, $product];
    }
}
