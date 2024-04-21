<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserActivityHistoryResource;
use App\Http\Resources\UserListResource;
use App\Models\Branch;
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

    public function __construct()
    {
        // Check if the 'permission' query parameter is present and set to 'true'
        $checkPermission = request()->query('permission') === 'True';
        // Conditionally apply permission middleware
        if ($checkPermission) {
            $this->middleware('permission:user:read')->only('index', 'show');
            $this->middleware('permission:user:create')->only('store');
            $this->middleware('permission:user:edit')->only('update');
            $this->middleware('permission:user:delete')->only('destroy'); // this api is still remain
        }
    }
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

        try {
            DB::beginTransaction();
            $createdUser = User::create([
                'name' => $validatedData['name'],
                'password' => Hash::make($validatedData['password']),
                'phone_number' => $validatedData['phone_number'],
                // 'address' => $validatedData['address'],
                'branch_id' => $validatedData['branch_id'],
            ]);

            // Assign the role
            $createdUser->assignRole($validatedData['role_id']);

            DB::commit();
            $message = 'User (' . $createdUser->name . ') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $getUser = User::findOrFail($id);

        try {
            $permissions = $getUser->getPermissionsViaRoles();
            $permissions->transform(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ];
            });

            $role = $getUser->roles;
            $role->transform(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            });

            $userData = new stdClass;
            $userData->user_code = $getUser->user_code;
            $userData->name = $getUser->name;
            $userData->branch = [
                'branch_id' => $getUser->branch_id,
                'branch_name' => $getUser->branch?->name ?? "",
            ];
            $userData->phone_number = $getUser->phone_number;

            $userData->role = $role[0];
            // $userData->permissions = $permissions;
            // $userData->role->permissions = $permissions;

            return $this->sendSuccessResponse("Success", Response::HTTP_OK, $userData);
        } catch (Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, string $id)
    {
        $updateUser = User::findOrFail($id);
        $validatedData = $request->validated();
        try {
            DB::beginTransaction();
            $previousBranchId = $updateUser->branch_id;
            $newBranchId = $validatedData['branch_id'];

            $updateUser->update([
                'name' => $validatedData['name'],
                'password' => Hash::make($validatedData['password']),
                'phone_number' => $validatedData['phone_number'],
                // 'address' => $validatedData['address'],
                'branch_id' => $validatedData['branch_id'],
            ]);

            if ($previousBranchId != $newBranchId) {
                $previousBranch = Branch::findOrFail($previousBranchId);
                $previousBranch->decrement('total_employee');

                // Increase total_employee count of the new branch
                $newBranch = Branch::findOrFail($newBranchId);
                $newBranch->increment('total_employee');
            }

            // Assign the role
            $updateUser->assignRole($validatedData['role_id']);

            DB::commit();
            $message = 'User (' . $updateUser->name . ') is updated successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $deleteUser = User::findOrFail($id);

        try {
            // check delete user has action in acivity logs
            if (count($deleteUser->actions) > 0) {
                return $this->sendErrorResponse('There are related data with ' . $deleteUser->name, Response::HTTP_CONFLICT);
            } else {
                $message = 'User (' . $deleteUser->name . ') is deleted successfully';
                $deleteUser->delete();
                return $this->sendSuccessResponse($message, Response::HTTP_OK);
            }
        } catch (Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
        $userData->user_code = $user->user_code;
        $userData->name = $user->name;
        $userData->branch = [
            'branch_id' => $user->branch_id,
            'branch_name' => $user->branch?->name ?? "",
        ];
        $userData->phone_number = $user->phone_number;

        $userData->role = $role[0];
        // $userData->permissions = $permissions;
        // $userData->role->permissions = $permissions;

        return $this->sendSuccessResponse("Success", Response::HTTP_OK, $userData);
    }

    public function getActivityHistory()
    {
        $userId = Auth::user()->id;
        $user = User::findOrFail($userId);

        $userActivities = Activity::causedBy($user)->latest()->take(10)->get();

        $resourceCollection = new UserActivityHistoryResource($userActivities);

        return $this->sendSuccessResponse("Success", Response::HTTP_OK, $resourceCollection);
    }

    public function getUserActivityByUserId($id)
    {
        $user = User::findOrFail($id);

        try {
            $userActivities = Activity::causedBy($user)->latest()->take(10)->get();
            $resourceCollection = new UserActivityHistoryResource($userActivities);

            return $this->sendSuccessResponse("Success", Response::HTTP_OK, $resourceCollection);
        } catch (Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
