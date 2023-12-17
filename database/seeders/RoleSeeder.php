<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = Permission::get();
        
        $roles = ['Admin', 'Manager', 'Supervisor', 'Cashier'];

        foreach ($roles as $role) {
            $createRole = Role::create(['name' => $role]);

            if ($role == "Admin") {
                $createRole->syncPermissions($permissions);
            }
            if ($role == "Cashier")
            {
                $itemPermissionsList = ['item:get', 'item:create', 'item:edit', 'item:detail', 'item:delete'];
                $cashier_permissions = Permission::whereIn('name', $itemPermissionsList)->get();
                $createRole->syncPermissions($cashier_permissions);
            }
        }
    }
}
