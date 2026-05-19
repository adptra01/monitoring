<?php

namespace Database\Seeders;

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
            'view users', 'view reports',
        ]);

        $supportRole = Role::updateOrCreate(['name' => 'support'], ['guard_name' => 'web']);
        $supportRole->syncPermissions([
            'view licenses', 'edit licenses',
        ]);

        $userRole = Role::updateOrCreate(['name' => 'user'], ['guard_name' => 'web']);
        $userRole->syncPermissions(['view licenses', 'view products']);
    }
}
