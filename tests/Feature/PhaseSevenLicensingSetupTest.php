<?php

namespace Tests\Feature;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Licensing\Services\LicenseService;
use App\Domains\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PhaseSevenLicensingSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_update_tenant_license_and_audit_is_recorded(): void
    {
        [$tenant, $admin] = $this->fixture();

        $this->actingAs($admin)->put(route('tenants.license.update', $tenant), [
            'license_key' => 'ZYNQ-CLIENT-2026',
            'license_status' => 'active',
            'subscription_expires_at' => now()->addMonth()->toDateString(),
            'grace_period_days' => 7,
            'active' => 1,
        ])->assertRedirect();

        $tenant->refresh();
        $this->assertSame('ZYNQ-CLIENT-2026', $tenant->license_key);
        $this->assertSame('active', $tenant->license_status);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'updated',
            'module' => 'licensing',
        ]);
    }

    public function test_disabled_tenant_user_is_blocked_but_super_admin_can_access_license_page(): void
    {
        [$tenant, $admin, $tenantAdmin] = $this->fixture(['license_status' => 'disabled']);

        $this->actingAs($tenantAdmin)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('tenants.license.edit', $tenant))->assertOk();
    }

    public function test_expired_subscription_allows_grace_period_then_blocks_after_grace(): void
    {
        [$tenant] = $this->fixture([
            'license_status' => 'active',
            'subscription_expires_at' => now()->subDays(2),
            'grace_period_days' => 3,
        ]);

        $status = app(LicenseService::class)->status($tenant->refresh());
        $this->assertFalse($status['blocked']);
        $this->assertSame('grace_period', $status['state']);

        $tenant->update(['subscription_expires_at' => now()->subDays(5), 'grace_period_days' => 3]);
        $this->assertTrue(app(LicenseService::class)->status($tenant->refresh())['blocked']);
    }

    public function test_onboarding_completion_and_bir_info_are_tenant_aware_and_audited(): void
    {
        [$tenant, $admin, $tenantAdmin] = $this->fixture();
        $otherTenant = Tenant::create([
            'business_name' => 'Other Tenant',
            'registered_address' => 'Other',
            'tin' => '777-000-000-000',
            'taxpayer_type' => 'VAT',
        ]);

        $this->actingAs($tenantAdmin)->put(route('settings.bir.update'), [
            'tenant_id' => $otherTenant->id,
            'registered_address' => 'Blocked',
            'tin' => '111-111-111-111',
            'taxpayer_type' => 'VAT',
            'bir_rdo_code' => '001',
        ])->assertForbidden();

        $this->actingAs($tenantAdmin)->put(route('settings.bir.update'), [
            'tenant_id' => $tenant->id,
            'registered_address' => 'Updated Registered Address',
            'tin' => '123-456-789-000',
            'taxpayer_type' => 'NON_VAT',
            'bir_rdo_code' => '043',
            'bir_registration_notes' => 'For CPA review.',
        ])->assertRedirect();

        $this->assertSame('NON_VAT', $tenant->refresh()->taxpayer_type);

        $this->actingAs($admin)->post(route('onboarding.complete', $tenant))->assertRedirect();
        $this->assertNotNull($tenant->refresh()->onboarding_completed_at);
        $this->assertGreaterThanOrEqual(2, AuditLog::where('tenant_id', $tenant->id)->count());
    }

    public function test_invoice_preview_and_compliance_checklist_render_bir_ready_warning(): void
    {
        [$tenant, $admin] = $this->fixture();

        $this->actingAs($admin)->get(route('invoice-preview.show'))
            ->assertOk()
            ->assertSee('BIR-ready, not automatically BIR-approved.');

        $this->actingAs($admin)->get(route('compliance.checklist'))
            ->assertOk()
            ->assertSee('BIR-ready, not automatically BIR-approved.');
    }

    private function fixture(array $tenantOverrides = []): array
    {
        foreach (['manage licenses', 'manage settings', 'view compliance'] as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate('Super Admin')->givePermissionTo(['manage licenses', 'manage settings', 'view compliance']);
        Role::findOrCreate('Tenant Admin')->givePermissionTo(['manage settings', 'view compliance']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tenant = Tenant::create(array_merge([
            'business_name' => 'Phase 7 Tenant',
            'registered_address' => 'Registered Address',
            'tin' => '700-000-000-001',
            'taxpayer_type' => 'VAT',
            'bir_rdo_code' => '044',
            'license_status' => 'active',
            'active' => true,
        ], $tenantOverrides));

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $tenantAdmin = User::factory()->create(['tenant_id' => $tenant->id]);
        $tenantAdmin->assignRole('Tenant Admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return [$tenant, $admin, $tenantAdmin];
    }
}
