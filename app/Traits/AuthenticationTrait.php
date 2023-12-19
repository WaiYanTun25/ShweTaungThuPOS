<?php

namespace App\Traits;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Response;
use stdClass;

trait AuthenticationTrait
{
   public function getUserRoleAndPermission($id, $token = null)
   {
    $user = User::select('id', 'name' , 'branch_id')->find($id);

    $permissions = $user->getPermissionsViaRoles();

    $permissions->transform(function ($permission) {
        return [
            'id' => $permission->id,
            'name' => $permission->name,
        ];
    });
    $role = $user->roles;
    $role->transform(function ($role) {
        return [
            'id' => $role->id,
            'name' => $role->name,
        ];
    });

    $userData = new stdClass;
    $userData->name = $user->name;
    $userData->branch = $user->branch_id ? $user->branch_id : 0;
    if($token) 
    {
        $userData->token = $token; 
    }
    $userData->role = $role[0];
    $userData->permissions = $permissions;
    // $userData->role->permissions = $permissions;

    return $this->sendSuccessResponse("Success", Response::HTTP_OK,$userData);
   }
}
