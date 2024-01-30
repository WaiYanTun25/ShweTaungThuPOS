<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserActivityHistoryResource;
use App\Http\Resources\UserListResource;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use stdClass;

class UserController extends ApiBaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $getUsers = User::select('*');
        $search = $request->query('searchBy');

        if ($search) {
            $getUsers->where('name', 'like', "%$search%");
        }

        // Handle order and column
        $order = $request->query('order', 'asc'); // default to asc if not provided
        $column = $request->query('column', 'id'); // default to id if not provided

        $getUsers->orderBy($column, $order);

        // // Add pagination
        $perPage = $request->query('perPage', 10); // default to 10 if not provided
        $users = $getUsers->paginate($perPage);

        $resourceCollection = new UserListResource($users);

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function create(UserRequest $request)
    {
        $validatedData = $request->validated();

        try{
            DB::beginTransaction();
            $createdUser = User::create([
                'name' => $validatedData['name'],
                'password' => Hash::make($validatedData['password']),
                'phone_number' => $validatedData['phone_number'],
                'role_id' => $validatedData['role_id'],
                'address' => $validatedData['address'],
                'branch_id' => $validatedData['branch_id'],
            ]);
           
            DB::commit();
            $message = 'User (' . $createdUser->name . ') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        }catch(Exception $e){
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, string $id)
    {
        return $request->all();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getAccountSetting()
    {
        $authUser = Auth::user();
        $user = User::findOrFail($authUser->id);

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
        $userData->branch_name = $user->branch->name;
        $userData->phone_number = $user->phone_number;

        $userData->role = $role[0];
        // $userData->permissions = $permissions;
        // $userData->role->permissions = $permissions;

        return $this->sendSuccessResponse("Success", Response::HTTP_OK,$userData);
    }

    public function getActivityHistory()
    {
        $userId = Auth::user()->id;
        $user = User::findOrFail($userId);

        $userActivities = Activity::causedBy($user)->latest()->take(10)->get();

        $resourceCollection = new UserActivityHistoryResource($userActivities);

        return $this->sendSuccessResponse("Success", Response::HTTP_OK,$resourceCollection);
    }

}
