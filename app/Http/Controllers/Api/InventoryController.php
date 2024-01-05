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
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Exception;
use stdClass;
use App\Models\Issue;
use App\Models\Damage;
use App\Models\Receive;
use App\Http\Resources\TransferResourceCollection;

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

    public function getIssuesReceivesAndDamages(Request $request)
    {
        // $getIssue = Issue::with(['transfer_details', 'transfer_details.unit', 'transfer_details.item'])->select('*');
        // $getReceive = Receive::with(['transfer_details', 'transfer_details.unit', 'transfer_details.item'])->select('*');
        // $getDamage = Damage::with(['transfer_details', 'transfer_details.unit', 'transfer_details.item'])->select('*');

        $getIssue = Issue::with(['transfer_details', 'transfer_details.unit', 'transfer_details.item'])
        ->select(['id', 'voucher_no', 'total_quantity','transaction_date', DB::raw("to_branch_id as branch_id"), DB::raw("'ISSUE' as type")]);

        $getReceive = Receive::with(['transfer_details', 'transfer_details.unit', 'transfer_details.item'])
            ->select(['id', 'voucher_no', 'total_quantity', 'transaction_date', DB::raw("from_branch_id as branch_id"), DB::raw("'RECEIVE' as type")]);
            // ->addSelect(DB::raw("from_branch_id as branch_id"));

        $getDamage = Damage::with(['transfer_details', 'transfer_details.unit', 'transfer_details.item'])
            ->select(['id', 'voucher_no', 'total_quantity', 'transaction_date', DB::raw("branch_id as branch_id"), DB::raw("'DAMAGE' as type")]);
            // ->addSelect(DB::raw("branch_id as branch_id"));

        $search = $request->query('searchBy');

        if ($search) {
            $getIssue->where('voucher_no', 'like', "%$search%")
                // ->orWhere('transaction_date', 'like', "%$search%")
                // ->orWhere('total_quantity', 'like', "%$search%")
                ->orWhereHas('transfer_details.item', function ($query) use ($search) {
                    $query->where('item_name', 'like', "%$search%");
                });

            $getReceive->where('voucher_no', 'like', "%$search%")
                // ->orWhere('transaction_date', 'like', "%$search%")
                // ->orWhere('total_quantity', 'like', "%$search%")
                ->orWhereHas('transfer_details.item', function ($query) use ($search) {
                    $query->where('item_name', 'like', "%$search%");
                });
            
            $getDamage->where('voucher_no', 'like', "%$search%")
                // ->orWhere('transaction_date', 'like', "%$search%")
                // ->orWhere('total_quantity', 'like', "%$search%");
                ->orWhereHas('transfer_details.item', function ($query) use ($search) {
                    $query->where('item_name', 'like', "%$search%");
                });
        }

        // Handle order and column
        $order = $request->query('order', 'asc'); // default to asc if not provided
        $column = $request->query('column', 'transaction_date'); // default to id if not provided
        // Combine the results using union
        $perPage = $request->query('perPage', 10); 
        // $results = $getIssue->union($getReceive)->orderBy($column, $order)->paginate($perPage);
        $results = $getIssue->union($getReceive)->union($getDamage)->orderBy($column, $order)->paginate($perPage);
        $resourceCollection = new TransferResourceCollection($results);

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);

         // $getIssue = Issue::with(['transfer_details', 'transfer_details.unit', 'transfer_details.item'])->select('*');
        // $getReceive = Receive::with(['transfer_details', 'transfer_details.unit', 'transfer_details.item'])->select('*');
        // $search = $request->query('searchBy');

        // if ($search) {
        //     $getIssue->where('voucher_no', 'like', "%$search%")
        //         ->orWhere('transaction_date', 'like', "%$search%")
        //         ->orWhere('total_quantity', 'like', "%$search%");
        // }

        // // Handle order and column
        // $order = $request->query('order', 'asc'); // default to asc if not provided
        // $column = $request->query('column', 'id'); // default to id if not provided

        // $getTransfer->orderBy($column, $order);

        // Add pagination
        // $perPage = $request->query('perPage', 10); // default to 10 if not provided
        // $transfers = $getTransfer->paginate($perPage);
    }

    public function getInventorySummary(Request $request)
    {
        $countItem = Item::count();
        $countIssueWithingOneMonth = 10;
        $countReceiveWithinOneMonth = 10;
        $coutnDamgeWithingOneMonth = 10;
        $countLowStockWithinOneMonth = 10;

        $result = new stdClass;
        
    }
}
