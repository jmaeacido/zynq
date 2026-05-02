<?php

namespace Tests\Feature;

use App\Domains\Branches\Models\Branch;
use App\Domains\Inventory\Models\InventoryStock;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Products\Models\Product;
use App\Domains\Products\Models\ProductCategory;
use App\Domains\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PhaseTwoInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_cross_tenant_product_access_is_blocked(): void
    {
        [$ownTenant, $ownBranch, $ownUser] = $this->tenantUser();
        [$otherTenant] = $this->tenantUser('Other Tenant', '200-000-000-002');
        $category = ProductCategory::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Category',
            'code' => 'OTH',
        ]);
        $product = Product::create([
            'tenant_id' => $otherTenant->id,
            'category_id' => $category->id,
            'sku' => 'OTH-001',
            'name' => 'Other Product',
            'unit' => 'pcs',
            'selling_price' => 100,
            'cost_price' => 50,
            'tax_type' => 'VATABLE',
        ]);

        $this->actingAs($ownUser)->get(route('products.edit', $product))->assertForbidden();

        $response = $this->actingAs($ownUser)->post(route('stock-movements.store'), [
            'tenant_id' => $ownTenant->id,
            'branch_id' => $ownBranch->id,
            'product_id' => $product->id,
            'movement_type' => 'stock_in',
            'quantity' => 5,
            'reason' => 'Cross-tenant attempt',
        ]);

        $response->assertSessionHasErrors('product_id');
        $this->assertDatabaseMissing('stock_movements', ['reason' => 'Cross-tenant attempt']);
    }

    public function test_stock_movement_creation_updates_inventory_balance(): void
    {
        [$tenant, $branch, $user, $product] = $this->tenantUserWithProduct();

        $this->actingAs($user)->post(route('stock-movements.store'), [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'movement_type' => 'stock_in',
            'quantity' => 12.5,
            'reference_number' => 'DR-100',
            'reason' => 'Initial stock-in',
        ])->assertRedirect(route('stock-movements.index'));

        $this->assertDatabaseHas('inventory_stocks', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 12.5,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'movement_type' => 'stock_in',
            'quantity_before' => 0,
            'quantity_after' => 12.5,
            'reference_number' => 'DR-100',
        ]);
    }

    public function test_stock_adjustment_sets_target_quantity_and_is_append_only(): void
    {
        [$tenant, $branch, $user, $product] = $this->tenantUserWithProduct();
        $service = new InventoryService();
        $service->stockIn($product, $branch, 10, 'Opening balance', $user);

        $movement = $service->adjust($product, $branch, 7, 'Cycle count', $user);

        $this->assertSame('adjustment', $movement->movement_type);
        $this->assertDatabaseHas('inventory_stocks', ['product_id' => $product->id, 'quantity_on_hand' => 7]);

        $this->expectException(LogicException::class);
        $movement->delete();
    }

    public function test_low_stock_detection_uses_reorder_point(): void
    {
        [$tenant, $branch, $user, $product] = $this->tenantUserWithProduct();
        $service = new InventoryService();
        $service->stockIn($product, $branch, 3, 'Initial quantity', $user);

        $stock = InventoryStock::where('tenant_id', $tenant->id)->where('branch_id', $branch->id)->where('product_id', $product->id)->firstOrFail();
        $service->setReorderPoint($stock, 5);

        $this->assertTrue($stock->refresh()->isLowStock());
        $this->assertTrue(InventoryStock::lowStock()->whereKey($stock->id)->exists());
    }

    private function tenantUser(string $name = 'Own Tenant', string $tin = '200-000-000-001'): array
    {
        Permission::findOrCreate('manage inventory');
        Role::findOrCreate('Inventory Staff')->givePermissionTo('manage inventory');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tenant = Tenant::create([
            'business_name' => $name,
            'registered_address' => $name.' Address',
            'tin' => $tin,
            'taxpayer_type' => 'VAT',
        ]);
        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'branch_name' => $name.' Branch',
            'branch_code' => substr(str_replace(' ', '', strtoupper($name)), 0, 8),
            'address' => $name.' Branch Address',
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id]);
        $user->assignRole('Inventory Staff');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return [$tenant, $branch, $user];
    }

    private function tenantUserWithProduct(): array
    {
        [$tenant, $branch, $user] = $this->tenantUser();
        $category = ProductCategory::create([
            'tenant_id' => $tenant->id,
            'name' => 'Beverages',
            'code' => 'BEV',
        ]);
        $product = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'sku' => 'SKU-001',
            'barcode' => '480000000001',
            'name' => 'Bottled Water',
            'unit' => 'bottle',
            'selling_price' => 25,
            'cost_price' => 12,
            'tax_type' => 'VATABLE',
        ]);

        return [$tenant, $branch, $user, $product];
    }
}
