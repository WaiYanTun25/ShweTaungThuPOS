<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitRequest;
use App\Models\Unit;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use stdClass;

class UnitController extends ApiBaseController
{

    public function __construct()
    {
        // Check if the 'permission' query parameter is present and set to 'true'
        $checkPermission = request()->query('permission') === 'True';
        // Conditionally apply permission middleware
        if ($checkPermission) {
            $this->middleware('permission:unit:read')->only('index', 'show');
            $this->middleware('permission:unit:create')->only('store');
            $this->middleware('permission:unit:edit')->only('update');
            $this->middleware('permission:unit:delete')->only('destroy'); // this api is still remain
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $getUnit = Unit::select('*');

        $search = $request->query('searchBy');

        if ($search)
        {
            $getUnit->where('name', 'like', "%$search%");
        }
         // Handle order and column
        $order = $request->query('order', 'asc'); // default to asc if not provided
        $column = $request->query('column', 'id'); // default to id if not provided

        $getUnit->orderBy($column, $order);

        $units = $getUnit->get();

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $units);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UnitRequest $request)
    {
        try{
            $createdUnit = new Unit();
            $createdUnit->name = $request->name;
            $createdUnit->save();
            $message = 'Unit (' . $createdUnit->name .') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        }catch(Exception $e){
            info($e->getMessage());
            return $this->sendErrorResponse('Error creating item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $getUnit = Unit::findOrFail($id);

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $getUnit ? $getUnit : new stdClass);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UnitRequest $request, string $id)
    {
        $updatedUnit = Unit::findOrFail($id);
        try{
            $updatedUnit->name = $request->name;
            $updatedUnit->save();
            $message = 'Unit (' . $updatedUnit->name .') is updated successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        }catch(Exception $e){
            info($e->getMessage());
            return $this->sendErrorResponse('Error creating item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $unit = Unit::findOrFail($id);
        try{
            DB::beginTransaction();
            if(true) {
                return $this->sendErrorResponse('There are related data with '.$unit->name, Response::HTTP_CONFLICT);
            }
            $unit->unitUnitDetails()->delete();
            $unit->delete();
            DB::commit();

            $message = 'Unit (' . $unit->item_name .') is deleted successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        }catch(Exception $e){
            DB::rollBack();
            info($e->getMessage());
            return $this->sendErrorResponse('Error deleting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
