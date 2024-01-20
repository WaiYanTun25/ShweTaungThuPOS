<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ItemRequest;
use App\Http\Resources\ItemDetailResource;
use App\Http\Resources\ItemListResource;
use App\Http\Resources\ItemResource;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\ItemUnitDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;
use stdClass;

use App\Traits\ItemTrait;
use Illuminate\Support\Facades\Auth;

class ItemController extends ApiBaseController
{
    use ItemTrait;

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
    // public function index(Request $request)
    // {
    //     // Filter
    //     $supplier_id = $request->query('supplier_id');
    //     $category_id = $request->query('category_id');
        
    //     $perPage = $request->query('perPage', 10);
    //     $search = $request->query('searchBy');
    //     $order = $request->query('order', 'asc');
    //     $column = $request->query('column', 'id');

    //     $branchId = Auth::user()->branch_id;
    //     $getItems = Inventory::with('item');

    //     if($supplier_id){
    //         $getItems->whereHas('item', function ($q) use ($supplier_id){
    //             $q->where('supplier_id', $supplier_id);
    //         });
    //     }

    //     if($category_id){
    //         $getItems->whereHas('item', function ($q) use ($category_id){
    //             $q->where('category_id', $category_id);
    //         });
    //     }
        
    //     if ($branchId != 0) {
    //         $getItems->where('branch_id', $branchId);
    //     }

    //     if ($search) {
    //         $getItems->whereHas('item', function ($query) use ($search) {
    //             $query->where('item_name', 'like', "%$search%")
    //                 ->orWhere('item_code', 'like', "%$search%");
    //         });
    //     }

    //     if($column == 'quantity'){
    //         $getItems->orderBy('quantity', $order);
    //     }

    //     if($request->query('report') == "True")
    //     {
    //         $results = $getItems->get();
    //         $resourceCollection = new ItemListResource($results, true);

    //         return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);
    //     }

    //     $items = $getItems->paginate($perPage);
    //     $resourceCollection = new ItemListResource($items);

    //     return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);
    // }

    public function index(Request $request)
    {
        // Filter
        $supplier_id = $request->query('supplier_id');
        $category_id = $request->query('category_id');
        
        $perPage = $request->query('perPage', 10);
        $search = $request->query('searchBy');
        $order = $request->query('order', 'asc');
        $column = $request->query('column', 'id');

        $branchId = Auth::user()->branch_id;
        // $getItems = Inventory::with('item');
        $getItems = Item::with(['inventories' => function ($query) use ($branchId) {
            if ($branchId != 0) {
                $query->where('branch_id', $branchId);
            }
        }])->withSum('inventories', 'quantity');

        if($supplier_id){
            $getItems->where('supplier_id', $supplier_id);
        }

        if($category_id){
            $getItems->where('category_id', $category_id);
        }

        if ($search) {
            $getItems->where('item_name', 'like', "%$search%")
                    ->orWhere('item_code', 'like', "%$search%");
        }

        if ($column == 'quantity') {
            // Order by total quantity for each item
            // $getItems->withCount(['inventories as total_quantity' => function ($query) use ($branchId) {
            //     if ($branchId != 0) {
            //         $query->where('branch_id', $branchId);
            //     }
            // }])
            // ->orderBy('total_quantity', $order);
            $getItems->orderBy('inventories_sum_quantity', $order);
        }

        if($request->query('report') == "True")
        {
            $results = $getItems->get();
            $resourceCollection = new ItemListResource($results, true);

            return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);
        }

        $items = $getItems->paginate($perPage);
        $resourceCollection = new ItemListResource($items);

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);
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

            foreach($request->unit_detail as $detail){
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
            return $this->sendErrorResponse('Error creating item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $getItems = Item::with(['itemUnitDetails','supplier', 'category'])->select('id' , 'item_code', 'category_id', 'supplier_id', 'item_name');

        $item = $getItems->findOrFail($id);

        $item['branch_with_quantities'] = Inventory::select('branches.name', DB::raw('SUM(quantity) as total_quantity'))
                ->join('branches', 'inventories.branch_id', '=', 'branches.id')
                ->groupBy('inventories.branch_id')
                ->where('inventories.item_id', $id)
                ->get();
        

        $result = new ItemDetailResource($item);
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $result);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ItemRequest $request, string $id)
    {
        $item = Item::findOrFail($id);
        try {
            DB::beginTransaction();

            $item->supplier_id = $request->supplier_id;
            $item->category_id = $request->category_id;
            $item->item_name = $request->item_name;
            $item->save();
            $item->itemUnitDetails()->delete();
            // Retrieve existing unit details
            // $existingUnitDetails = $item->ItemUnitDetails;
            // Updated unit details from the request
            // $updatedUnitDetails = $request->input('unit_detail', []);
            
            // Get the IDs of existing and updated unit details
            // $existingIds = $existingUnitDetails->pluck('id')->toArray();
            // $updatedIds = collect($updatedUnitDetails)->pluck('id')->toArray();
            $item->itemUnitDetails()->createMany($request->unit_detail);
          
            // Identify new, updated, and deleted unit details
            // $newIds = array_diff($updatedIds, $existingIds);
            // $updatedIds = array_intersect($updatedIds, $existingIds);
            // $deletedIds = array_diff($existingIds, $updatedIds);

            // if($updatedIds)
            // {
            //     foreach($updatedUnitDetails as $detail)
            //     {
            //         $updateItemUnitDetail = ItemUnitDetail::findOrFail($detail['id']);
            //         $updateItemUnitDetail->item_id = $item->id;
            //         $updateItemUnitDetail->unit_id = $detail['unit_id'];  
            //         $updateItemUnitDetail->rate = $detail['rate'];        
            //         $updateItemUnitDetail->vip_price = $detail['vip_price'];
            //         $updateItemUnitDetail->retail_price = $detail['retail_price'];  
            //         $updateItemUnitDetail->wholesale_price = $detail['wholesale_price'];  
            //         $updateItemUnitDetail->reorder_level = $detail['reorder_level'];  
            //         $updateItemUnitDetail->reorder_period = $detail['reorder_period'];
            //         $updateItemUnitDetail->save();
            //     }
            // }

            // if ($deletedIds) {
            //     $deleteItemUnitDetails = ItemUnitDetail::whereIn('id', $deletedIds)->delete(); 
            // }
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
            if($this->checkItemHasRelatedData($id)) {
                return $this->sendErrorResponse('There are related data with '.$item->item_name, Response::HTTP_CONFLICT);
            }
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

    public function showByCode($item_code)
    {
        $item = Item::with(['itemUnitDetails'])->where('item_code', $item_code)->firstOrFail();
        
        $result = new ItemResource($item);

        return $this->sendSuccessResponse('Success', Response::HTTP_OK, $result);
    }
}
