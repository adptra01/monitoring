<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view licenses', 'create licenses', 'edit licenses', 'delete licenses',
            'view products', 'create products', 'edit products', 'delete products',
            'view devices', 'view subscriptions',
            'view activation requests', 'approve activation requests',
            'view audit logs', 'view teams', 'manage teams',
            'view users', 'manage users', 'manage roles',
            'view reports', 'manage settings',
        ];

        foreach ($permissions as $name) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['name' => $name, 'guard_name' => 'web'],
            );
        }

        $adminRole = Role::updateOrCreate(['name' => 'admin'], ['guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());

        $managerRole = Role::updateOrCreate(['name' => 'manager'], ['guard_name' => 'web']);
        $managerRole->syncPermissions([
            'view licenses', 'create licenses', 'edit licenses',
            'view products', 'create products', 'edit products',
            'view devices', 'view subscriptions',
            'view activation requests', 'approve activation requests',
            'view teams', 'manage teams',
            'view users', 'view reports',
        ]);

        $supportRole = Role::updateOrCreate(['name' => 'support'], ['guard_name' => 'web']);
        $supportRole->syncPermissions([
            'view licenses', 'edit licenses',
            'view devices',
            'view activation requests', 'approve activation requests',
            'view audit logs',
        ]);

        $userRole = Role::updateOrCreate(['name' => 'user'], ['guard_name' => 'web']);
        $userRole->syncPermissions(['view licenses', 'view products']);

        $this->createAdminUsers();
    }

    private function createAdminUsers(): void
    {
        $admins = [
            ['name' => 'Admin', 'email' => 'admin@admin.com', 'password' => 'password'],
            ['name' => 'Super Admin', 'email' => 'superadmin@admin.com', 'password' => 'password'],
        ];

        foreach ($admins as $adminData) {
            $admin = User::updateOrCreate(
                ['email' => $adminData['email']],
                [
                    'name' => $adminData['name'],
                    'email' => $adminData['email'],
                    'password' => bcrypt($adminData['password']),
                    'email_verified_at' => now(),
                    'is_admin' => true,
                ]
            );
            $admin->assignRole('admin');
        }
    }
}
