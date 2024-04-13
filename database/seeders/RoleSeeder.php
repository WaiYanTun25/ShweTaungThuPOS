<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use stdClass;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminPermissions = Permission::where('name', 'Like', '%read%')->get();

        $managerPermissions = Permission::get();

        $supervisorPermissions = Permission::where('name', 'Like', '%branch%')
        ->Orwhere('name', 'Like', '%sale%')
        ->Orwhere('name', 'Like', '%purchase%')
        ->get();

        $cashierPermissions = Permission::where('name' , 'Like', '%sale%')
        ->OrWhere('name' , 'Like', '%purchase%')
        ->OrWHere('name' , 'Like', '%item%')
        ->OrWHere('name' , 'Like', '%customer%')
        ->OrWHere('name' , 'Like', '%supplier%')
        ->get();

        $permissionList = new stdClass;
        $permissionList->Admin = $adminPermissions;
        $permissionList->Manager = $managerPermissions;
        $permissionList->Supervisor = $supervisorPermissions;
        $permissionList->Cashier = $cashierPermissions;

        $roles = ['Admin', 'Manager', 'Supervisor', 'Cashier'];

        foreach ($roles as $role) {
            $createRole = Role::create(['name' => $role]);
            
            $createRole->syncPermissions($permissionList->$role);
        }
    }
}
