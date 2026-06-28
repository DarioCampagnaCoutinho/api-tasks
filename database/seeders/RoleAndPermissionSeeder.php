<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'tasks.viewAny',
            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.delete',
            'users.viewAny',
            'users.view',
            'users.update',
            'users.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions);

        $user = Role::firstOrCreate(['name' => 'user']);
        $user->syncPermissions([
            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.delete',
        ]);
    }
}
