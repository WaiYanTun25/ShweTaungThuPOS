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
        $permissions = ['branches', 'edit branches', 'show branches', 'create branches', 'delete branches'];
        $rolePermissionsList = ['get-roles', 'create-role', 'edit-role', 'delete-role'];
        $allPermissions = array_merge($permissions, $rolePermissionsList);

        foreach($allPermissions as $permission) 
        {
            $createPermission = new Permission();
            $createPermission->name = $permission;
            $createPermission->save();
        }
        
    }
}
