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
        // Handle order and column
        $order = $request->query('order', 'asc'); // default to asc if not provided
        $column = $request->query('column', 'transaction_date'); // default to id if not provided

        if (Auth::user()->branch_id != 0) {
            $itemDetails = ItemUnitDetail::with('item')
                ->join('inventories', function ($join) {
                    $join->on('item_unit_details.item_id', '=', 'inventories.item_id')
                        ->on('item_unit_details.unit_id', '=', 'inventories.unit_id')
                        ->where('inventories.branch_id', Auth::user()->branch_id)
                        ->where('inventories.quantity', '<', DB::raw('item_unit_details.reorder_level'));
                })

                ->leftJoin('transfer_details', function ($join) {
                    $join->on('item_unit_details.item_id', '=', 'transfer_details.item_id')
                        ->on('item_unit_details.unit_id', '=', 'transfer_details.unit_id')
                        ->where('transfer_details.voucher_no', 'like', 'INV-R%')
                        ->whereRaw('transfer_details.id = (SELECT MAX(id) FROM transfer_details WHERE item_unit_details.item_id = transfer_details.item_id AND item_unit_details.unit_id = transfer_details.unit_id AND transfer_details.voucher_no LIKE "INV-R%")');
                })

                ->leftJoin('receives', function ($join) {
                    $join->on('transfer_details.voucher_no', '=', 'receives.voucher_no');
                })

                // Only add the join and search condition if $search is provided
                ->when($search, function ($query) use ($search) {
                    $query->join('items', 'item_unit_details.item_id', '=', 'items.id')
                        ->where('items.item_name', 'like', '%' . $search . '%')
                        ->orWhere('items.item_code', 'like', '%' . $search . '%');
                })
                ->select(
                    // 'receives.voucher_no as receives_voucher_no',
                    // 'receives.transaction_date as receives_transaction_date',
                    'receives.transaction_date',
                    'item_unit_details.*',
                    'inventories.quantity as quantity',
                    'inventories.branch_id as branch_id'
                )
                ->orderBy($column, $order) 
                ->paginate($perPage);
                // return $itemDetails;
            $result = new LowStockResource($itemDetails);
        } else {
            $itemDetails = ItemUnitDetail::with('item')
                ->join('inventories', function ($join) {
                    $join->on('item_unit_details.item_id', '=', 'inventories.item_id')
                        ->on('item_unit_details.unit_id', '=', 'inventories.unit_id')
                        ->where('inventories.quantity', '<', DB::raw('item_unit_details.reorder_level'));
                })

                ->leftJoin('transfer_details', function ($join) {
                    $join->on('item_unit_details.item_id', '=', 'transfer_details.item_id')
                        ->on('item_unit_details.unit_id', '=', 'transfer_details.unit_id')
                        ->where('transfer_details.voucher_no', 'like', 'INV-R%')
                        ->whereRaw('transfer_details.id = (SELECT MAX(id) FROM transfer_details WHERE item_unit_details.item_id = transfer_details.item_id AND item_unit_details.unit_id = transfer_details.unit_id AND transfer_details.voucher_no LIKE "INV-R%")');
                })

                ->leftJoin('receives', function ($join) {
                    $join->on('transfer_details.voucher_no', '=', 'receives.voucher_no');
                })

                // Only add the join and search condition if $search is provided
                ->when($search, function ($query) use ($search) {
                    $query->join('items', 'item_unit_details.item_id', '=', 'items.id')
                        ->where('items.item_name', 'like', '%' . $search . '%')
                        ->orWhere('items.item_code', 'like', '%' . $search . '%');
                })
                ->select(
                    'receives.transaction_date',
                    'item_unit_details.*',
                    'inventories.quantity as quantity',
                    'inventories.branch_id as branch_id'
                )
                ->paginate($perPage);
                    // return $itemDetails;
            $result = new LowStockResource($itemDetails);
        }

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $result);
    }
}
