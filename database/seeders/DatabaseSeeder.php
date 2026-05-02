<?php

namespace Database\Seeders;

use App\Domains\Branches\Models\Branch;
use App\Domains\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage tenants',
            'manage licenses',
            'manage branches',
            'manage terminals',
            'manage settings',
            'manage users',
            'create sales',
            'approve voids',
            'approve refunds',
            'view reports',
            'export reports',
            'view compliance',
            'manage inventory',
            'manage cash sessions',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $roles = [
            'Super Admin' => $permissions,
            'Tenant Admin' => ['manage branches', 'manage terminals', 'manage settings', 'manage users', 'view reports', 'export reports', 'view compliance', 'manage inventory', 'manage cash sessions', 'approve voids', 'approve refunds'],
            'Branch Manager' => ['manage terminals', 'view reports', 'export reports', 'view compliance', 'manage inventory', 'manage cash sessions', 'approve voids', 'approve refunds'],
            'Cashier' => ['create sales', 'manage cash sessions', 'view compliance'],
            'Auditor' => ['view reports', 'export reports', 'view compliance'],
            'Inventory Staff' => ['manage inventory', 'view compliance'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            Role::findOrCreate($roleName)->syncPermissions($rolePermissions);
        }

        $admin = User::firstOrCreate(
            ['email' => env('ZYNQ_SUPER_ADMIN_EMAIL', 'admin@zynq.local')],
            ['name' => 'ZYNQ Super Admin', 'password' => env('ZYNQ_SUPER_ADMIN_PASSWORD', 'password')]
        );

        $admin->assignRole('Super Admin');

        $demoTenant = Tenant::firstOrCreate(
            ['tin' => '000-000-000-000'],
            [
                'business_name' => 'ZYNQ Demo Tenant',
                'trade_name' => 'ZYNQ Demo',
                'registered_address' => 'Demo Registered Address',
                'taxpayer_type' => 'VAT',
                'bir_rdo_code' => '000',
                'contact_name' => 'Demo Contact',
                'contact_email' => 'demo@zynq.local',
                'license_status' => 'active',
                'invoice_footer' => 'Demo account only. BIR-ready, not automatically BIR-approved.',
                'active' => true,
            ]
        );

        $demoBranch = Branch::firstOrCreate(
            ['tenant_id' => $demoTenant->id, 'branch_code' => 'DEMO'],
            [
                'branch_name' => 'Demo Branch',
                'address' => 'Demo Branch Address',
                'bir_registered_address' => 'Demo BIR Registered Address',
                'status' => 'active',
            ]
        );

        $demoPassword = env('ZYNQ_DEMO_USER_PASSWORD', 'password');
        $demoUsers = [
            'Tenant Admin' => ['name' => 'ZYNQ Tenant Admin', 'email' => 'tenant.admin@zynq.local'],
            'Branch Manager' => ['name' => 'ZYNQ Branch Manager', 'email' => 'branch.manager@zynq.local'],
            'Cashier' => ['name' => 'ZYNQ Cashier', 'email' => 'cashier@zynq.local'],
            'Auditor' => ['name' => 'ZYNQ Auditor', 'email' => 'auditor@zynq.local'],
            'Inventory Staff' => ['name' => 'ZYNQ Inventory Staff', 'email' => 'inventory@zynq.local'],
        ];

        foreach ($demoUsers as $roleName => $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'tenant_id' => $demoTenant->id,
                    'branch_id' => $roleName === 'Tenant Admin' ? null : $demoBranch->id,
                    'name' => $userData['name'],
                    'password' => $demoPassword,
                    'active' => true,
                ]
            );

            $user->syncRoles([$roleName]);
        }
    }
}
