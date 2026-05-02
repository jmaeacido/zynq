<?php

namespace Database\Seeders;

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
    }
}
