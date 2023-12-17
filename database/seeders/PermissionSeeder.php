<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branchPermissionList = ['branch:get', 'branch:create', 'branch:edit','branch:detail','branch:delete'];
        $categoryPermissionList = ['category:get', 'category:create', 'category:edit','category:detail','category:delete'];
        $supplierPermissionList = ['supplier:get', 'supplier:create', 'supplier:edit','supplier:detail','supplier:delete'];
        $customerPermissionList = ['customer:get', 'customer:create', 'customer:edit','customer:detail','customer:delete'];
        $rolePermissionsList = ['role:get', 'role:create', 'role:edit','role:detail', 'role:delete'];
        $itemPermissionsList = ['item:get', 'item:create', 'item:edit', 'item:detail', 'item:delete'];
        $registerPermissions = ['auth:register'];

        $allPermissions = array_merge($categoryPermissionList, $supplierPermissionList,$customerPermissionList, $registerPermissions, $branchPermissionList, $rolePermissionsList, $itemPermissionsList);

        foreach($allPermissions as $permission)
        {
            Permission::create([
                'name' => $permission,
                // 'guard_name' => 'sanctum',
            ]);
        }
    }
}
