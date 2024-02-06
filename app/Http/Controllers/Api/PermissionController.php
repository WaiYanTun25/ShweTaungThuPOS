<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends ApiBaseController
{
    public function index()
    {
        $permissions = Permission::get();

        $formattedPermissions = [];

        foreach ($permissions as $permission) {
            $nameParts = explode(':', $permission['name']);
            
            if (count($nameParts) === 2) {
                $resource = $nameParts[0];
                $action = $nameParts[1];
                
                if (!isset($formattedPermissions[$resource])) {
                    $formattedPermissions[$resource] = [
                        'name' => $resource,
                        'permissions' => [],
                    ];
                }

                $formattedPermissions[$resource]['permissions'][] = [
                    'id' => $permission['id'],
                    'name' => $action,
                ];
            }
        }

        // Convert the associative array to indexed array
        $formattedPermissions = array_values($formattedPermissions);
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $formattedPermissions);
    }
}

