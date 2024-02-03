<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class RoleController extends ApiBaseController
{
    public function __construct()
    {
        $this->middleware('permission:role:get')->only('index');
        $this->middleware('permission:role:create')->only('store');
        $this->middleware('permission:role:detail')->only('show');
        $this->middleware('permission:role:edit')->only('update');
        $this->middleware('permission:role:delete')->only('delete');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $getRoles = Role::select('id', 'name');

        $search = $request->query('searchBy');
        if ($search) {
            $getRoles->where('name', 'like', "%$search%");
        }

        $order = $request->query('order', 'asc'); // default to asc if not provided
        $column = $request->query('column', 'id');

        $getRoles->orderBy($column, $order);
        $getRoles = $getRoles->get()->map(function ($role) {
            $userCount = User::role($role->name)->count();
            $role->user_count = $userCount;
            return $role;
        });
        
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $getRoles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request)
    {
        try{
            DB::beginTransaction();

            $role = new Role();
            $role->name = $request->name;
            $role->save();

            $role->syncPermissions($request->permission_ids);
            DB::commit();

            $message = "Role (". $role->name .") is created successfully.";
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        }catch(Exception $e){
            DB::rollBack();
            info($e->getMessage());
            return $this->sendErrorResponse('error',Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $role = Role::with('permissions:id,name')->findOrFail($id);

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $role);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, string $id)
    {
        $role = Role::findOrFail($id);
        try{
            DB::beginTransaction();

            $role->name = $request->name;
            $role->save();

            // Get the users with the old role
            $users = $role->users;

             // Sync the permissions for the role
            $role->syncPermissions($request->permission_ids);

            DB::commit();
            $message = 'Role ('. $role->name .') is updated successfully';

            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        }catch(Exception $e){
            DB::rollBack();
            info($e->getMessage());
            return $this->sendErrorResponse('error',Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);
        try{
            if ($role->users()->exists()) {
                return $this->sendErrorResponse('There are related data with '.$role->name, Response::HTTP_CONFLICT);
            }

            // for log activity
            // this is manually log
            activity()
            ->useLog('ROLE')
            ->causedBy(Auth::user())
            ->setEvent('deleted')
            ->performedOn($role)
            // ->withProperties(['Reveieve' => $damage , 'ReceiveDetail' => $damage->transfer_details])
            ->log('{userName} deleted the Role ('. $role->name. ')');

            $role->delete();

            $message = 'Role ('.$role->name.') is deleted successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        }catch(Exception $e){
            info($e->getMessage());
            return $this->sendErrorResponse('Something went wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
