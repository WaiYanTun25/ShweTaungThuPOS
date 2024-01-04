<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitConvertRequest;
use App\Models\Inventory;
use App\Models\UnitConvert;
use App\Models\UnitConvertDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Resources\UnitConvertResource;
use App\Http\Resources\UnitConvertDetailResource;
use App\Http\Resources\UnitConvertDetailCollection;
use Illuminate\Http\Exceptions\HttpResponseException;

class UnitConvertController extends ApiBaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $unitConverts = UnitConvert::with('convertDetail')->where('branch_id', Auth::user()->branch_id)
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        $unitConverts = UnitConvertResource::collection($unitConverts);
        return $this->sendSuccessResponse('UnitConverts retrieved successfully', Response::HTTP_OK, $unitConverts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UnitConvertRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create a new UnitConvert instance
            $unitConvert = new UnitConvert();
            $unitConvert->branch_id = Auth::user()->branch_id;
            $unitConvert->item_id = $request->validated()['item_id'];
            $unitConvert->save();

            // Create a new UnitConvertDetail instance
            $unitConvertDetail = new UnitConvertDetail();
            $unitConvertDetail->unit_convert_id = $unitConvert->id;
            $unitConvertDetail->from_unit_id = $request->validated()['convert_details']['from_unit_id'];
            $unitConvertDetail->from_qty = $request->validated()['convert_details']['from_qty'];
            $unitConvertDetail->to_unit_id = $request->validated()['convert_details']['to_unit_id'];
            $unitConvertDetail->to_qty = $request->validated()['convert_details']['to_qty'];
            $unitConvertDetail->save();

            // Get the inventory for the from_unit_id
            $inventory = Inventory::where('branch_id', Auth::user()->branch_id)
                ->where('item_id', $request->validated()['item_id'])
                ->where('unit_id', $request->validated()['convert_details']['from_unit_id'])
                ->first();

            // Get the inventory for the to_unit_id
            $addInventory = Inventory::where('branch_id', Auth::user()->branch_id)
                ->where('item_id', $request->validated()['item_id'])
                ->where('unit_id', $request->validated()['convert_details']['to_unit_id'])
                ->first();
            info($request->validated()['convert_details']['from_qty']);
            // Decrement the inventory for the from_unit_id
            if ($inventory) {
                $inventory->decrement('quantity', $request->validated()['convert_details']['from_qty']);
            }

            // Increment the inventory for the to_unit_id
            if ($addInventory) {
                $addInventory->increment('quantity', $request->validated()['convert_details']['to_qty']);
            } else {
                // Create a new Inventory instance for the to_unit_id
                $addInventory = new Inventory();
                $addInventory->branch_id = Auth::user()->branch_id;
                $addInventory->item_id = $request->validated()['item_id'];
                $addInventory->unit_id = $request->validated()['convert_details']['to_unit_id'];
                $addInventory->quantity = $request->validated()['convert_details']['to_qty'];
                $addInventory->save();
            }

            DB::commit();
            return $this->sendSuccessResponse('success', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();
            info($e->getMessage());
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $unitConvert = UnitConvert::with('convertDetail')->where('branch_id', Auth::user()->branch_id)
                        ->findOrFail($id);
        $unitConvert = new UnitConvertDetailResource($unitConvert);
        return $this->sendSuccessResponse('UnitConverts retrieved successfully', Response::HTTP_OK, $unitConvert);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UnitConvertRequest $request, string $id)
    {
        //
    }

    /**
     * Destroy a unit conversion record.
     *
     * @param string $id The ID of the unit conversion record.
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        // Retrieve the unit conversion record with the associated convert detail
        $unitConvert = UnitConvert::with('convertDetail')->findOrFail($id);

        try {
            // Start a database transaction
            DB::beginTransaction();

            // Retrieve the inventory record for the 'from' unit
            $fromUnitInventory = Inventory::where('branch_id', Auth::user()->branch_id)
                ->where('item_id', $unitConvert->item_id)
                ->where('unit_id', $unitConvert->convertDetail->from_unit_id)
                ->first();

            // Increment the quantity of the 'from' unit inventory if it exists
            if ($fromUnitInventory) {
                $fromUnitInventory->increment('quantity', $unitConvert->convertDetail->from_qty);
            }

            // Retrieve the inventory record for the 'to' unit
            $toUnitInventory = Inventory::where('branch_id', Auth::user()->branch_id)
                ->where('item_id', $unitConvert->item_id)
                ->where('unit_id', $unitConvert->convertDetail->to_unit_id)
                ->first();

            // Decrement the quantity of the 'to' unit inventory if it exists and has sufficient quantity
            if ($toUnitInventory && $toUnitInventory->quantity > $unitConvert->convertDetail->to_qty) {
                $toUnitInventory->decrement('quantity', $unitConvert->convertDetail->to_qty);
            } else {
                // Return an error response if the 'to' unit inventory is insufficient
                return $this->sendErrorResponse('Insufficient quantity', Response::HTTP_BAD_REQUEST);
            }

            // Delete the convert detail and unit convert records
            $unitConvert->convertDetail()->delete();
            $unitConvert->delete();

            // Commit the database transaction
            DB::commit();

            // Return a success response
            return $this->sendSuccessResponse('UnitConvert deleted successfully', Response::HTTP_OK);
        } catch (\Exception $e) {
            // Rollback the database transaction and return an error response
            DB::rollBack();
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
