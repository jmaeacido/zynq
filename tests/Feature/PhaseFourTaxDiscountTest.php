<?php

namespace Tests\Feature;

use App\Domains\Branches\Models\Branch;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Products\Models\Product;
use App\Domains\Products\Models\ProductCategory;
use App\Domains\Reports\Services\TaxDiscountReportService;
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

class PhaseFourTaxDiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_vatable_sale_persists_vat_summary(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product] = $this->fixture(price: 112, taxType: 'VATABLE');
        $this->stock($product, $branch, $cashier);

        $this->sell($cashier, $branch, $terminal, $product, 112);
        $tax = Sale::firstOrFail()->taxSummary;

        $this->assertEquals(112.00, (float) $tax->gross_sales);
        $this->assertEquals(100.00, (float) $tax->vatable_sales);
        $this->assertEquals(12.00, (float) $tax->vat_amount);
        $this->assertEquals(112.00, (float) $tax->total_amount_due);
    }

    public function test_non_vat_tenant_records_non_vat_sales_without_vat(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product] = $this->fixture(taxpayerType: 'NON_VAT', price: 100, taxType: 'VATABLE', tin: '400-000-000-002');
        $this->stock($product, $branch, $cashier);

        $this->sell($cashier, $branch, $terminal, $product, 100);
        $tax = Sale::firstOrFail()->taxSummary;

        $this->assertEquals(0.00, (float) $tax->vat_amount);
        $this->assertEquals(0.00, (float) $tax->vatable_sales);
        $this->assertEquals(100.00, (float) $tax->non_vat_sales);
    }

    public function test_vat_exempt_and_zero_rated_products_are_separated(): void
    {
        [$tenant, $branch, $terminal, $cashier, $exempt] = $this->fixture(price: 50, taxType: 'VAT_EXEMPT', tin: '400-000-000-003');
        $zero = $this->product($tenant, 'ZERO-1', 40, 'ZERO_RATED');
        $this->stock($exempt, $branch, $cashier);
        $this->stock($zero, $branch, $cashier);

        $this->actingAs($cashier)->post(route('pos.sales.store'), [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
            'items' => [
                ['product_id' => $exempt->id, 'quantity' => 1],
                ['product_id' => $zero->id, 'quantity' => 1],
            ],
            'payments' => [['payment_method' => 'cash', 'amount' => 90, 'amount_tendered' => 90]],
        ])->assertRedirect();

        $tax = Sale::firstOrFail()->taxSummary;
        $this->assertEquals(50.00, (float) $tax->vat_exempt_sales);
        $this->assertEquals(40.00, (float) $tax->zero_rated_sales);
        $this->assertEquals(0.00, (float) $tax->vat_amount);
    }

    public function test_discount_application_reduces_total_and_persists_discount(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product] = $this->fixture(price: 100, taxType: 'NON_VAT', tin: '400-000-000-004');
        $this->stock($product, $branch, $cashier);

        $this->actingAs($cashier)->post(route('pos.sales.store'), [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'discounts' => [['discount_type' => 'promo', 'value_type' => 'percent', 'value' => 10, 'reason' => 'Launch promo']],
            'payments' => [['payment_method' => 'cash', 'amount' => 90, 'amount_tendered' => 100]],
        ])->assertRedirect();

        $sale = Sale::with(['discounts', 'taxSummary'])->firstOrFail();
        $this->assertEquals(10.00, (float) $sale->discount_total);
        $this->assertEquals(90.00, (float) $sale->total_amount);
        $this->assertEquals(10.00, (float) $sale->discounts->first()->amount);
        $this->assertEquals(90.00, (float) $sale->taxSummary->total_amount_due);
    }

    public function test_report_totals_use_persisted_tax_and_discount_rows(): void
    {
        [$tenant, $branch, $terminal, $cashier, $product] = $this->fixture(price: 112, taxType: 'VATABLE', tin: '400-000-000-005');
        $this->stock($product, $branch, $cashier);
        $this->sell($cashier, $branch, $terminal, $product, 112);

        $service = new TaxDiscountReportService();
        $vatTotals = $service->vatSales($cashier);

        $this->assertEquals(100.00, $vatTotals['vatable_sales']);
        $this->assertEquals(12.00, $vatTotals['vat_amount']);
    }

    private function sell(User $cashier, Branch $branch, Terminal $terminal, Product $product, float $amount): void
    {
        $this->actingAs($cashier)->post(route('pos.sales.store'), [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'cash', 'amount' => $amount, 'amount_tendered' => $amount]],
        ])->assertRedirect();
    }

    private function stock(Product $product, Branch $branch, User $cashier): void
    {
        (new InventoryService())->stockIn($product, $branch, 10, 'Opening stock', $cashier);
    }

    private function fixture(string $taxpayerType = 'VAT', float $price = 100, string $taxType = 'VATABLE', string $tin = '400-000-000-001'): array
    {
        foreach (['create sales', 'view reports'] as $permission) {
            Permission::findOrCreate($permission);
        }
        Role::findOrCreate('Cashier')->givePermissionTo('create sales');
        Role::findOrCreate('Auditor')->givePermissionTo('view reports');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tenant = Tenant::create([
            'business_name' => 'Phase 4 Tenant '.$tin,
            'registered_address' => 'Address',
            'tin' => $tin,
            'taxpayer_type' => $taxpayerType,
        ]);
        $branch = Branch::create(['tenant_id' => $tenant->id, 'branch_name' => 'Main', 'branch_code' => 'B'.$tenant->id, 'address' => 'Branch']);
        $terminal = Terminal::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'terminal_name' => 'POS',
            'terminal_code' => 'T'.$tenant->id,
            'machine_identification_number' => 'MIN',
            'permit_to_use_number' => 'PTU',
            'serial_number' => 'SN',
            'software_version' => '13.7.0',
        ]);
        $cashier = User::factory()->create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id]);
        $cashier->assignRole('Cashier');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        (new CashSessionService())->open($branch, $terminal, $cashier, 100);

        return [$tenant, $branch, $terminal, $cashier, $this->product($tenant, 'SKU'.$tenant->id, $price, $taxType)];
    }

    private function product(Tenant $tenant, string $sku, float $price, string $taxType): Product
    {
        $category = ProductCategory::firstOrCreate(['tenant_id' => $tenant->id, 'code' => 'GEN'], ['name' => 'General']);

        return Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'sku' => $sku,
            'name' => $sku,
            'unit' => 'pcs',
            'selling_price' => $price,
            'cost_price' => 1,
            'tax_type' => $taxType,
        ]);
    }
}
