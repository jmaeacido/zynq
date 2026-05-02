<?php

namespace Tests\Feature;

use App\Domains\Branches\Models\Branch;
use App\Domains\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PhaseOneAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_cannot_create_branch_for_another_tenant(): void
    {
        Permission::findOrCreate('manage branches');
        Role::findOrCreate('Tenant Admin')->givePermissionTo('manage branches');

        $ownTenant = Tenant::create([
            'business_name' => 'Own Tenant',
            'registered_address' => 'Own Address',
            'tin' => '100-000-000-001',
            'taxpayer_type' => 'VAT',
        ]);
        $otherTenant = Tenant::create([
            'business_name' => 'Other Tenant',
            'registered_address' => 'Other Address',
            'tin' => '100-000-000-002',
            'taxpayer_type' => 'NON_VAT',
        ]);
        $user = User::factory()->create(['tenant_id' => $ownTenant->id]);
        $user->assignRole('Tenant Admin');

        $response = $this->actingAs($user)->post(route('branches.store'), [
            'tenant_id' => $otherTenant->id,
            'branch_name' => 'Leak Branch',
            'branch_code' => 'LEAK',
            'address' => 'Blocked Address',
            'status' => 'active',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('branches', ['branch_code' => 'LEAK']);
    }

    public function test_branch_route_rejects_cross_tenant_access(): void
    {
        Permission::findOrCreate('manage branches');
        Role::findOrCreate('Tenant Admin')->givePermissionTo('manage branches');

        $ownTenant = Tenant::create([
            'business_name' => 'Own Tenant',
            'registered_address' => 'Own Address',
            'tin' => '100-000-000-003',
            'taxpayer_type' => 'VAT',
        ]);
        $otherTenant = Tenant::create([
            'business_name' => 'Other Tenant',
            'registered_address' => 'Other Address',
            'tin' => '100-000-000-004',
            'taxpayer_type' => 'VAT',
        ]);
        $branch = Branch::create([
            'tenant_id' => $otherTenant->id,
            'branch_name' => 'Other Branch',
            'branch_code' => 'OTH',
            'address' => 'Other Address',
        ]);
        $user = User::factory()->create(['tenant_id' => $ownTenant->id]);
        $user->assignRole('Tenant Admin');

        $this->actingAs($user)->get(route('branches.edit', $branch))->assertForbidden();
    }
}
