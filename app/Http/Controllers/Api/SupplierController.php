<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierRequest;
use App\Models\Item;
use App\Models\Supplier;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SupplierController extends ApiBaseController
{
    public function __construct()
    {
        $this->middleware('permission:supplier:get')->only('index');
        $this->middleware('permission:supplier:create')->only('store');
        $this->middleware('permission:supplier:detail')->only('show');
        $this->middleware('permission:supplier:edit')->only('update');
        $this->middleware('permission:supplier:delete')->only('delete');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $getSupplier = Supplier::select('*');

        $search = $request->query('searchBy');

        if ($search)
        {
            $getSupplier->where('name', 'like', "%$search%")
                    ->orWhere('phone_number', 'like', "%$search%")
                    ->orWhere('address', 'like', "%$search%")
                    ->orWhere('township', 'like', "%$search%")
                    ->orWhere('city', 'like', "%$search%")
                    ->orWhere('join_date', 'like', "%$search%");
        }
         // Handle order and column
        $order = $request->query('order', 'asc'); // default to asc if not provided
        $column = $request->query('column', 'id'); // default to id if not provided

        $getSupplier->orderBy($column, $order);

        $suppliers = $getSupplier->get();

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $suppliers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SupplierRequest $request)
    {
        try{
            $createdSupplier = new Supplier();
            $createdSupplier->name = $request->name;
            $createdSupplier->address = $request->address;
            $createdSupplier->phone_number = $request->phone_number;
            $createdSupplier->township = $request->township;
            $createdSupplier->city = $request->city;
            $createdSupplier->save();

            $message = 'Supplier ('.$createdSupplier->name.') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        }catch(Exception $e){
            info($e->getMessage());
            return $this->sendErrorResponse('Something went wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
    public function update(SupplierRequest $request, string $id)
    {
        $updatedSupplier = Supplier::findOrFail($id);
        try{
            $updatedSupplier->name = $request->name;
            $updatedSupplier->address = $request->address;
            $updatedSupplier->phone_number = $request->phone_number;
            $updatedSupplier->township = $request->township;
            $updatedSupplier->city = $request->city;
            $updatedSupplier->save();

            $message = 'Supplier ('.$updatedSupplier->name.') is updated successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        }catch(Exception $e){
            info($e->getMessage());
            return $this->sendErrorResponse('Something went wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $supplier = Supplier::findOrFail($id);
        try{
            $checkSupplierExist = Item::where('supplier_id', $id)->first();
            
            if($checkSupplierExist)
            {
                return $this->sendErrorResponse('There are related data with '.$supplier->name, Response::HTTP_CONFLICT);
            }
            $supplier->delete();

            $message = 'Supplier ('.$supplier->name.') is deleted successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        }catch(Exception $e){
            info($e->getMessage());
            return $this->sendErrorResponse('Something went wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
