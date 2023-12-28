<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LowStockResource;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\ItemUnitDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends ApiBaseController
{
    public function getLowStockInventories(Request $request)
    {
        $perPage = $request->query('perPage', 10);
       
        $search = $request->query('searchBy');
        if(Auth::user()->branch_id != 0){
            $itemDetails = ItemUnitDetail::with('item')
            ->join('inventories', function ($join) {
                $join->on('item_unit_details.item_id', '=', 'inventories.item_id')
                    ->on('item_unit_details.unit_id', '=', 'inventories.unit_id')
                    ->where('inventories.branch_id', Auth::user()->branch_id)
                    ->where('inventories.quantity', '<', DB::raw('item_unit_details.reorder_level'));
            })
            // Only add the join and search condition if $search is provided
            ->when($search, function ($query) use ($search) {
                    $query->join('items', 'item_unit_details.item_id', '=', 'items.id')
                          ->where('items.item_name', 'like', '%' . $search . '%');
                })
            ->select('item_unit_details.*', 'inventories.quantity as inventory_quantity', 'inventories.branch_id as branch_id')
            ->paginate($perPage);

            $result = new LowStockResource($itemDetails);
        }else{
            $itemDetails = ItemUnitDetail::with('item')
            ->join('inventories', function ($join) {
                $join->on('item_unit_details.item_id', '=', 'inventories.item_id')
                    ->on('item_unit_details.unit_id', '=', 'inventories.unit_id')
                    ->where('inventories.quantity', '<', DB::raw('item_unit_details.reorder_level'));
            })
            // Only add the join and search condition if $search is provided
            ->when($search, function ($query) use ($search) {
                    $query->join('items', 'item_unit_details.item_id', '=', 'items.id')
                          ->where('items.item_name', 'like', '%' . $search . '%');
                })
            ->select('item_unit_details.*', 'inventories.quantity as inventory_quantity', 'inventories.branch_id as branch_id')
            ->paginate($perPage);

            $result = new LowStockResource($itemDetails);
        }

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $result);
    }
}
