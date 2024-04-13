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
        $branchPermissionList = ['branch:read', 'branch:create', 'branch:edit','branch:delete'];
        // $categoryPermissionList = ['category:read', 'category:create', 'category:edit','category:delete'];
        $supplierPermissionList = ['supplier:read', 'supplier:create', 'supplier:edit','supplier:delete'];
        $customerPermissionList = ['customer:read', 'customer:create', 'customer:edit','customer:delete'];
        $rolePermissionsList = ['role:read', 'role:create', 'role:edit', 'role:delete'];
        $itemPermissionsList = ['item:read', 'item:create', 'item:edit', 'item:delete'];
        $salesPermissionList = ['sales:read', 'sales:create', 'sales:edit','sales:delete'];
        $purchasesPermissionList = ['purchases:read', 'purchases:create', 'purchases:edit','purchases:delete'];
        $registerPermissions = ['auth:register'];

        $allPermissions = array_merge(
            $supplierPermissionList,
            $customerPermissionList, 
            $registerPermissions, $branchPermissionList, $rolePermissionsList, $itemPermissionsList, $salesPermissionList, $purchasesPermissionList);

        foreach($allPermissions as $permission)
        {
            Permission::create([
                'name' => $permission
            ]);
        }
    }
}
