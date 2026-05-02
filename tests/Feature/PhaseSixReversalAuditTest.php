<?php

namespace Tests\Feature;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Branches\Models\Branch;
use App\Domains\Inventory\Models\InventoryStock;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Products\Models\Product;
use App\Domains\Products\Models\ProductCategory;
use App\Domains\Sales\Models\FinancialLedgerEntry;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Models\SaleReversal;
use App\Domains\Sales\Services\CashSessionService;
use App\Domains\Sales\Services\SaleReversalService;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Terminals\Models\Terminal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PhaseSixReversalAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_void_sale_creates_reversal_ledger_audit_and_hash(): void
    {
        [$sale, $manager] = $this->completedSale();

        app(SaleReversalService::class)->void($sale, $manager, 'Wrong transaction', false);

        $reversal = SaleReversal::firstOrFail();
        $this->assertSame('void', $reversal->type);
        $this->assertNotEmpty($reversal->transaction_hash);
        $this->assertDatabaseHas('financial_ledger_entries', ['entry_type' => 'void', 'amount' => -25]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'void', 'module' => 'Sales']);
        $this->assertEquals(25, (float) $sale->refresh()->total_amount);
    }

    public function test_full_refund_and_partial_refund_workflows(): void
    {
        [$sale, $manager] = $this->completedSale(quantity: 2);

        app(SaleReversalService::class)->refund($sale, $manager, 'Customer return', false, [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1]]);
        $this->assertDatabaseHas('sale_reversals', ['type' => 'partial_refund', 'amount' => 25]);

        app(SaleReversalService::class)->refund($sale, $manager, 'Full return', false);
        $this->assertDatabaseHas('sale_reversals', ['type' => 'refund', 'amount' => 50]);
    }

    public function test_approval_requirement_blocks_cashier_reversal(): void
    {
        [$sale, $manager, $cashier] = $this->completedSale();

        $this->expectException(\InvalidArgumentException::class);
        app(SaleReversalService::class)->void($sale, $cashier, 'No approval', false);
    }

    public function test_ledger_entries_are_append_only(): void
    {
        [$sale, $manager] = $this->completedSale();
        app(SaleReversalService::class)->void($sale, $manager, 'Wrong transaction', false);

        $this->expectException(LogicException::class);
        FinancialLedgerEntry::where('entry_type', 'void')->firstOrFail()->delete();
    }

    public function test_stock_reversal_option_returns_stock(): void
    {
        [$sale, $manager] = $this->completedSale();
        $stock = InventoryStock::where('product_id', $sale->items->first()->product_id)->firstOrFail();
        $this->assertEquals(9, (float) $stock->quantity_on_hand);

        app(SaleReversalService::class)->void($sale, $manager, 'Return stock', true);

        $this->assertEquals(10, (float) $stock->refresh()->quantity_on_hand);
    }

    public function test_reversal_blocked_after_cash_session_close(): void
    {
        [$sale, $manager] = $this->completedSale();
        app(CashSessionService::class)->close($sale->cashSession, 125);

        $this->expectException(\InvalidArgumentException::class);
        app(SaleReversalService::class)->void($sale, $manager, 'Too late', false);
    }

    private function completedSale(float $quantity = 1): array
    {
        foreach (['create sales', 'approve voids', 'approve refunds', 'view reports'] as $permission) {
            Permission::findOrCreate($permission);
        }
        Role::findOrCreate('Cashier')->givePermissionTo('create sales');
        Role::findOrCreate('Branch Manager')->givePermissionTo(['approve voids', 'approve refunds', 'view reports']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tenant = Tenant::create(['business_name' => 'P6 Tenant', 'registered_address' => 'Address', 'tin' => fake()->unique()->numerify('600-###-###-###'), 'taxpayer_type' => 'VAT']);
        $branch = Branch::create(['tenant_id' => $tenant->id, 'branch_name' => 'Main', 'branch_code' => 'P6'.$tenant->id, 'address' => 'Branch']);
        $terminal = Terminal::create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'terminal_name' => 'POS', 'terminal_code' => 'P6'.$tenant->id, 'machine_identification_number' => 'MIN', 'permit_to_use_number' => 'PTU', 'serial_number' => 'SN', 'software_version' => '13.7.0']);
        $cashier = User::factory()->create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id]);
        $manager = User::factory()->create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id]);
        $cashier->assignRole('Cashier');
        $manager->assignRole('Branch Manager');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $category = ProductCategory::create(['tenant_id' => $tenant->id, 'name' => 'General', 'code' => 'GEN']);
        $product = Product::create(['tenant_id' => $tenant->id, 'category_id' => $category->id, 'sku' => 'P6-'.$tenant->id, 'name' => 'P6 Product', 'unit' => 'pcs', 'selling_price' => 25, 'cost_price' => 10, 'tax_type' => 'VATABLE']);
        app(CashSessionService::class)->open($branch, $terminal, $cashier, 100);
        app(InventoryService::class)->stockIn($product, $branch, 10, 'Opening', $cashier);

        $this->actingAs($cashier)->post(route('pos.sales.store'), [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
            'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
            'payments' => [['payment_method' => 'cash', 'amount' => 25 * $quantity, 'amount_tendered' => 25 * $quantity]],
        ])->assertRedirect();

        return [Sale::with(['items.product', 'cashSession', 'branch'])->firstOrFail(), $manager, $cashier];
    }
}
