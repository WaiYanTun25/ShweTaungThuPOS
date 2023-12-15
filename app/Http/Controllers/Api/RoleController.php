<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class RoleController extends ApiBaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $getRoles = Role::select('id', 'name')->get();
        
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $getRoles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all() , [
            'name' => 'required'
        ]);

        if($validator->fails())
        {
            return $this->sendErrorResponse('error', Response::HTTP_UNPROCESSABLE_ENTITY, $validator->errors());
        }

        $role = new Role();
        $role->name = $request->name;
        $role->save();

        return $this->sendSuccessResponse('success', Response::HTTP_CREATED, $role);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all() , [
            'name' => 'required'
        ]);

        if($validator->fails())
        {
            return $this->sendErrorResponse('error', Response::HTTP_UNPROCESSABLE_ENTITY, $validator->errors());
        }

        $role = Role::findOrFail($id);
        $role->name = $request->name;
        $role->save();

        return $this->sendSuccessResponse('success', Response::HTTP_CREATED, $role);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
