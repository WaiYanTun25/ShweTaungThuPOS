<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ItemRequest;
use App\Models\Item;
use App\Models\ItemUnitDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class ItemController extends ApiBaseController
{
    public function __construct()
    {
        $this->middleware('permission:item:get')->only('index');
        $this->middleware('permission:item:create')->only('store');
        $this->middleware('permission:item:detail')->only('show');
        $this->middleware('permission:item:edit')->only('update');
        $this->middleware('permission:item:delete')->only('delete');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $getItems = Item::select('id' , 'item_code', 'category_id', 'supplier_id', 'item_name');

        $search = $request->query('searchBy');
        if ($search)
        {
            $getItems->where('item_code', 'like', "%$search%")
                    ->andWhere('item_name', 'like', "%$search%");
        }
         // Retrieve the 'filter-by' parameter from the query string
         $filterBy = $request->input('filterby');

         if ($filterBy) {
            $filterByArray = json_decode($filterBy, true);

            // Access category_ids and supplier_ids from the filterByArray
            $categoryIds = $filterByArray['category_ids'] ?? [];
            $supplierIds = $filterByArray['supplier_ids'] ?? [];

            // Check if category_ids is not empty, then apply the whereIn clause
            if (!empty($categoryIds)) {
                $getItems->whereIn('category_id', $categoryIds);
            }

            // Check if supplier_ids is not empty, then apply the whereIn clause
            if (!empty($supplierIds)) {
                $getItems->whereIn('supplier_id', $supplierIds);
            }
        }
        $items = $getItems->get();

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $items);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ItemRequest $request)
    {
        try{
            DB::beginTransaction();

            $createItem = new Item();
            $createItem->category_id = $request->category_id;
            $createItem->supplier_id = $request->supplier_id;
            $createItem->item_name = $request->item_name;
            $createItem->save();
            info($createItem);

            foreach($request->unit_detail as $detail){
                // info($detail);
                $createItemUnit = new ItemUnitDetail();
                $createItemUnit->item_id = $createItem->id;
                $createItemUnit->unit_id = $detail['unit_id'];  
                $createItemUnit->rate = $detail['rate'];        
                $createItemUnit->vip_price = $detail['vip_price'];
                $createItemUnit->retail_price = $detail['retail_price'];  
                $createItemUnit->wholesale_price = $detail['wholesale_price'];  
                $createItemUnit->reorder_level = $detail['reorder_level'];  
                $createItemUnit->reorder_period = $detail['reorder_period'];
                $createItemUnit->save();
            }
            $message = 'Item (' . $createItem->item_name .') is created successfully';

            DB::commit();
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        }catch(Exception $e){
            DB::rollBack();
            info($e->getMessage());
            return $this->sendErrorResponse('Error creating item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
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
    public function update(ItemRequest $request, string $id)
    {
        $item = Item::findOrFail($id);
        try {
            DB::beginTransaction();
            // Retrieve existing unit details
            $existingUnitDetails = $item->ItemUnitDetails;
            // Updated unit details from the request
            $updatedUnitDetails = $request->input('unit_detail', []);
            
            // Get the IDs of existing and updated unit details
            $existingIds = $existingUnitDetails->pluck('id')->toArray();
            $updatedIds = collect($updatedUnitDetails)->pluck('id')->toArray();
          
            // Identify new, updated, and deleted unit details
            // $newIds = array_diff($updatedIds, $existingIds);
            $updatedIds = array_intersect($updatedIds, $existingIds);
            $deletedIds = array_diff($existingIds, $updatedIds);

            if($updatedIds)
            {
                foreach($updatedUnitDetails as $detail)
                {
                    $updateItemUnitDetail = ItemUnitDetail::findOrFail($detail['id']);
                    $updateItemUnitDetail->item_id = $item->id;
                    $updateItemUnitDetail->unit_id = $detail['unit_id'];  
                    $updateItemUnitDetail->rate = $detail['rate'];        
                    $updateItemUnitDetail->vip_price = $detail['vip_price'];
                    $updateItemUnitDetail->retail_price = $detail['retail_price'];  
                    $updateItemUnitDetail->wholesale_price = $detail['wholesale_price'];  
                    $updateItemUnitDetail->reorder_level = $detail['reorder_level'];  
                    $updateItemUnitDetail->reorder_period = $detail['reorder_period'];
                    $updateItemUnitDetail->save();
                }
            }

            if ($deletedIds) {
                $deleteItemUnitDetails = ItemUnitDetail::whereIn('id', $deletedIds)->delete(); 
            }
            $message = 'Item (' . $item->item_name .') is updated successfully';
            DB::commit();
    
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendErrorResponse('Error updating item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $item = Item::findOrFail($id);
        try{
            DB::beginTransaction();
            $item->ItemUnitDetails()->delete();
            $item->delete();
            DB::commit();

            $message = 'Item (' . $item->item_name .') is deleted successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        }catch(Exception $e){
            DB::rollBack();
            info($e->getMessage());
            return $this->sendErrorResponse('Error deleting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
