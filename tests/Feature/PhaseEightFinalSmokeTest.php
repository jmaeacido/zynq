<?php

namespace Tests\Feature;

use App\Domains\Branches\Models\Branch;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Products\Models\Product;
use App\Domains\Products\Models\ProductCategory;
use App\Domains\Sales\Services\CashSessionService;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Terminals\Models\Terminal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PhaseEightFinalSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_navigation_pages_open_for_super_admin(): void
    {
        [$tenant, $admin] = $this->fixture();

        $routes = [
            route('dashboard'),
            route('pos.checkout'),
            route('sales.index'),
            route('reports.daily-sales'),
            route('reports.vat-sales'),
            route('tenants.license.edit', $tenant),
            route('onboarding.index'),
            route('invoice-preview.show', ['tenant_id' => $tenant->id, 'template' => 'a4']),
            route('compliance.checklist'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_pos_empty_cart_is_rejected_and_zero_mixed_payment_rows_are_ignored(): void
    {
        [$tenant, $admin] = $this->fixture();
        [$branch, $terminal, $product] = $this->sellingFixture($tenant, $admin);

        $this->actingAs($admin)->post(route('pos.sales.store'), [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
            'items' => [],
            'payments' => [['payment_method' => 'cash', 'amount' => 100, 'amount_tendered' => 100]],
        ])->assertSessionHasErrors('items');

        $this->actingAs($admin)->post(route('pos.sales.store'), [
            'branch_id' => $branch->id,
            'terminal_id' => $terminal->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payments' => [
                ['payment_method' => 'cash', 'amount' => 25, 'amount_tendered' => 25],
                ['payment_method' => 'card', 'amount' => 0, 'reference_number' => null],
                ['payment_method' => 'e_wallet', 'amount' => 0, 'reference_number' => null],
            ],
        ])->assertRedirect();

        $this->assertDatabaseCount('sale_payments', 1);
    }

    public function test_report_export_buttons_have_csv_handlers(): void
    {
        [$tenant, $admin] = $this->fixture();
        [$branch, $terminal, $product, $session] = $this->sellingFixture($tenant, $admin);

        $urls = [
            route('reports.daily-sales', ['export' => 'csv']),
            route('reports.vat-sales', ['export' => 'csv']),
            route('reports.non-vat-sales', ['export' => 'csv']),
            route('reports.discounts', ['export' => 'csv']),
            route('reports.voids', ['export' => 'csv']),
            route('reports.refunds', ['export' => 'csv']),
            route('reports.audit-trail', ['export' => 'csv']),
            route('readings.x', [$session, 'export' => 'csv']),
            route('readings.cashier', [$session, 'export' => 'csv']),
            route('readings.terminal', [$session, 'export' => 'csv']),
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($admin)->get($url)->assertOk();
            $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        }
    }

    public function test_settings_and_terminal_routes_block_cross_tenant_access(): void
    {
        [$tenant, $admin] = $this->fixture();
        $otherTenant = Tenant::create([
            'business_name' => 'Other Tenant',
            'registered_address' => 'Other',
            'tin' => '801-000-000-002',
            'taxpayer_type' => 'VAT',
            'license_status' => 'active',
            'active' => true,
        ]);
        $branch = Branch::create(['tenant_id' => $otherTenant->id, 'branch_name' => 'Other Branch', 'branch_code' => 'OB', 'address' => 'Other']);
        $terminal = Terminal::create([
            'tenant_id' => $otherTenant->id,
            'branch_id' => $branch->id,
            'terminal_name' => 'Other POS',
            'terminal_code' => 'OPOS',
            'machine_identification_number' => 'MIN',
            'permit_to_use_number' => 'PTU',
            'serial_number' => 'SN',
            'software_version' => '13.7.0',
        ]);
        $tenantAdmin = User::factory()->create(['tenant_id' => $tenant->id]);
        $tenantAdmin->assignRole('Tenant Admin');

        $this->actingAs($tenantAdmin)->put(route('settings.update'), [
            'tenant_id' => $otherTenant->id,
            'vat_rate' => 12,
            'invoice_title_default' => 'Sales Invoice',
            'compliance_contact_email' => 'qa@example.test',
        ])->assertForbidden();

        $this->actingAs($tenantAdmin)->get(route('terminals.edit', $terminal))->assertForbidden();
    }

    private function fixture(): array
    {
        $permissions = [
            'manage tenants',
            'manage licenses',
            'manage branches',
            'manage terminals',
            'manage settings',
            'create sales',
            'view reports',
            'view compliance',
            'manage inventory',
            'manage cash sessions',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate('Super Admin')->syncPermissions($permissions);
        Role::findOrCreate('Tenant Admin')->syncPermissions(['manage settings', 'manage terminals', 'view compliance']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tenant = Tenant::create([
            'business_name' => 'Phase 8 Tenant',
            'trade_name' => 'P8 Trade',
            'registered_address' => 'Registered Address',
            'tin' => '800-000-000-001',
            'taxpayer_type' => 'VAT',
            'bir_rdo_code' => '043',
            'invoice_footer' => 'Sample footer',
            'license_status' => 'active',
            'active' => true,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return [$tenant, $admin];
    }

    private function sellingFixture(Tenant $tenant, User $admin): array
    {
        $branch = Branch::create(['tenant_id' => $tenant->id, 'branch_name' => 'Main', 'branch_code' => 'MAIN', 'address' => 'Branch']);
        $terminal = Terminal::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'terminal_name' => 'Main POS',
            'terminal_code' => 'POS01',
            'machine_identification_number' => 'MIN',
            'permit_to_use_number' => 'PTU',
            'serial_number' => 'SN',
            'software_version' => '13.7.0',
        ]);
        $category = ProductCategory::create(['tenant_id' => $tenant->id, 'name' => 'General', 'code' => 'GEN']);
        $product = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'sku' => 'P8-001',
            'name' => 'QA Product',
            'unit' => 'pcs',
            'selling_price' => 25,
            'cost_price' => 10,
            'tax_type' => 'VATABLE',
        ]);

        $session = app(CashSessionService::class)->open($branch, $terminal, $admin, 100);
        app(InventoryService::class)->stockIn($product, $branch, 10, 'QA stock', $admin);

        return [$branch, $terminal, $product, $session];
    }
}
