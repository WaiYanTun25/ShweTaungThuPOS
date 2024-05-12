<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\AuthenticationTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use stdClass;

class AuthenticationController extends ApiBaseController
{
    use AuthenticationTrait;

    public function __construct()
    {
        $checkPermission = request()->query('permission') === 'True';
        // Conditionally apply permission middleware
        if ($checkPermission) {
            $this->middleware('permission:auth:register')->only('registerUser');
        }
    }

    public function loginUser (Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'password' => 'required'
        ]);

        if($validator->fails())
        {
            return $this->sendErrorResponse("Error", Response::HTTP_INTERNAL_SERVER_ERROR, $validator->errors());
        }

        $user = User::where('name', $request->name)->first();

        if(empty($user)){
            return $this->sendErrorResponse("Wrong username", Response::HTTP_BAD_REQUEST);
        }

        if(Hash::check($request->password, $user->password)){
            $permissions = $user->getPermissionsViaRoles();
            
            $token = $user->createToken('Shwe Taung Thu',[$permissions])->plainTextToken;
           
            return $this->getUserRoleAndPermission($user->id, $token);
        }else{
            return $this->sendErrorResponse("Wrong Password",  Response::HTTP_BAD_REQUEST);
        }
    }

    public function registerUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required | unique:users',
            'password' => 'required|confirmed|min:6',
            'role_id' => 'required',
            'branch_id' => 'nullable'
        ]); 

        if($validator->fails())
        {
            return $this->sendErrorResponse('Validation Error',Response::HTTP_BAD_REQUEST , $validator->errors());
        }

        $name = $request->name;
        $password = $request->password;
        $branchId = $request->branch_id ?? 0;
        $roleId = $request->role_id;

        $createUser = new User();
        $createUser->name = $name;
        $createUser->password = Hash::make($password);
        $createUser->branch_id = $branchId;
        $createUser->save();

        $role = Role::find($roleId);
        $createUser->assignRole($role);
        
        $message = 'User '. $createUser->name. ' is created successfully';
        return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
    }

    public function logoutUser(Request $request)
    {
        $user = Auth::user();

        // Revoke the current user's access token
        $user->currentAccessToken()->delete();

        return $this->sendSuccessResponse('User logged out successfully', Response::HTTP_OK);
    }

    public function getCurrentUserRoleAndPermission()
    {
        info('here');
        $userId = Auth::user()->id;
        
        return $this->getUserRoleAndPermission($userId);
    }

    public function changeUserPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => [
                'required',
                'min:6',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
            ],
        ]);

        if($validator->fails())
        {
            return $this->sendErrorResponse("Validation Error", Response::HTTP_INTERNAL_SERVER_ERROR, $validator->errors());
        }
        $user = User::findOrFail(Auth::user()->id);

        // Check if the provided old password is correct
        if (!Hash::check($request->old_password, $user->password)) {
            return $this->sendErrorResponse("Validation Error", Response::HTTP_INTERNAL_SERVER_ERROR, ['old_password' => ['Incorrect old password']]);
            // throw ValidationException::withMessages(['old_password' => ['Incorrect old password']]);
        }

        // Update the user's password with the new one
        // Update the user's password with the new one
        $user->update(['password' => Hash::make($request->new_password)]);

        return $this->sendSuccessResponse('Change password successfully', Response::HTTP_OK);
    }
}
