<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use stdClass;

class AuthenticationController extends ApiBaseController
{
    public function loginUser (Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required | email:rfc,dns',
            'password' => 'required'
        ]);

        if($validator->fails())
        {
            return $this->sendErrorResponse("Error", Response::HTTP_INTERNAL_SERVER_ERROR, $validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        if(empty($user)){
            return $this->sendErrorResponse("Wrong Email", Response::HTTP_BAD_REQUEST);
        }

        if(Hash::check($request->password, $user->password)){
            $permissions = $user->getPermissionsViaRoles();

            $permissions->transform(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ];
            });

            $token = $user->createToken('Shwe Taung Thu')->plainTextToken;

            $userData = new stdClass;
            $userData->name = $user->name;
            $userData->email = $user->email;
            $userData->branch = $user->branch_id ? $user->branch_id : 0;

            return $this->sendSuccessResponse("Success", Response::HTTP_OK, [
                "token" => $token,
                "user" => $userData,
                "role" => $user->getRoleNames()->first(),
                "permissions" => $permissions,
                // "permissions" => $user->roles->permissions
            ]);
        }else{
            return $this->sendErrorResponse("Wrong Password",  Response::HTTP_BAD_REQUEST);
        }
    }

    public function registerUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required | email:rfc,dns | unique:users',
            'password' => 'required',
            'branch_id' => 'nullable'
        ]); 

        if($validator->fails())
        {
            return $this->sendErrorResponse('Validation Error',Response::HTTP_BAD_REQUEST , $validator->errors());
        }
        $email = $request->email;
        $password = $request->password;
        $branchId = $request->branch_id;



        
    }
}
