<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class NewSeederV1 extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** Damage, Receive, Issue */
        $damagePermissionList = ['damage:read', 'damage:create', 'damage:edit','damage:delete'];
        $issuePermissionList = ['issue:read', 'issue:create', 'issue:edit','issue:delete'];
        $receivePermissionList = ['receive:read', 'receive:create', 'receive:edit','receive:delete'];

        /** Others */
        $categoryPermissionList = ['category:read', 'category:create', 'category:edit','category:delete'];
        $cityPermissionList = ['city:read', 'city:create', 'city:edit','city:delete'];
        $paymentMethodPermissionList = ['payment:read', 'payment:create', 'payment:edit','payment:delete'];
        $townshipPermissionList = ['township:read', 'township:create', 'township:edit','township:delete'];
        $unitPermissionList = ['unit:read', 'unit:create', 'unit:edit','unit:delete'];
        $userPermissionList = ['user:read', 'user:create', 'user:edit','user:delete'];
        
        /** Password */
        $crackPasswordPermissionList = ['password:read', 'password:create', 'password:edit','password:delete'];

        /** Merge */
        $mergePermissions = array_merge(
            $damagePermissionList,
            $issuePermissionList,
            $receivePermissionList,
            $categoryPermissionList,
            $cityPermissionList,
            $paymentMethodPermissionList,
            $townshipPermissionList,
            $unitPermissionList,
            $userPermissionList,
            $crackPasswordPermissionList
        );

        foreach($mergePermissions as $permission)
        {
            Permission::create([
                'name' => $permission
            ]);
        }

        $cashierRole = Role::find(4);

        $cashierPermissions = Permission::where(function ($query) {
            $query->where('name', 'like', '%damage%')
                ->orWhere('name', 'like', '%issue%')
                ->orWhere('name', 'like', '%receive%');
        })->get();
        
        info($cashierPermissions);

        $cashierRole->syncPermissions($cashierPermissions);

        $adminRole = Role::find(1);
        $adminPermissions = Permission::where(function ($query) {
            $query->where('name', 'like', '%password%')
                ->orWhere('name', 'like', '%category%')
                ->orWhere('name', 'like', '%city%')
                ->orWhere('name', 'like', '%payment%') // Corrected typo 'pyament%' to 'payment%'
                ->orWhere('name', 'like', '%township%')
                ->orWhere('name', 'like', '%unit%')
                ->orWhere('name', 'like', '%user%');
        })->pluck('name')->toArray();

        info($adminPermissions);
        $adminRole->syncPermissions($adminPermissions);

    }
}
