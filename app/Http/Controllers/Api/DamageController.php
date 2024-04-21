<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DamageRequest;
use App\Http\Resources\DamageDetailResource;
use App\Http\Resources\DamageItemListResource;
use App\Models\Damage;
use App\Models\TransferDetail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Traits\TransactionTrait;
use Illuminate\Support\Facades\Auth;

class DamageController extends ApiBaseController
{
    use TransactionTrait;

    public function __construct()
    {
        // Check if the 'permission' query parameter is present and set to 'true'
        $checkPermission = request()->query('permission') === 'True';
        // Conditionally apply permission middleware
        if ($checkPermission) {
            $this->middleware('permission:damage:read')->only('index', 'show');
            $this->middleware('permission:damage:create')->only('store');
            $this->middleware('permission:damage:edit')->only('update');
            $this->middleware('permission:damage:delete')->only('destroy'); // this api is still remain
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('perPage', 10);
        $search = $request->query('searchBy');

        // FILTER
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');
        $category_id = $request->query('category_id');
        $supplier_id = $request->query('supplier_id');

        $branch_id = Auth::user()->branch_id;

        $damageItems = TransferDetail::with('damage')->where('transfer_details.voucher_no', 'like', 'INV-D%');

        // filters
        if($startDate && $endDate){
            $damageItems->whereHas('damage', function ($q) use ($startDate, $endDate){
                $q->whereDate('transaction_date', '>=', $startDate)
                ->whereDate('transaction_date', '<=', $endDate);
            });
        }
        if($category_id){
            $damageItems->whereHas('item', function ($q) use ($category_id){
                $q->where('category_id', $category_id);
            });
        }
        if($supplier_id){
            $damageItems->whereHas('item', function ($q) use ($supplier_id){
                $q->where('supplier_id', $supplier_id);
            });
        }

        if($branch_id != 0) {
            $damageItems->whereHas('damage', function ($q) use ($branch_id){
                $q->where('branch_id', $branch_id);
            });
        }

        if ($search) {
            $damageItems->where(function ($query) use ($search) {
                $query->whereHas('item', function ($itemQuery) use ($search) {
                        $itemQuery->where('item_name', 'like', '%' . $search . '%')
                                    ->orWhere('item_code', 'like', '%' . $search . '%');
                    })
                    ->orWhere('transfer_details.voucher_no', 'like', '%' . $search . '%');
            });
        }

        $order = $request->query('order', 'asc');
        $column = $request->query('column', 'transaction_date');

        // Join the damages table to be able to use orderBy on its columns
        $damageItems->leftJoin('damages', 'transfer_details.voucher_no', '=', 'damages.voucher_no');

        // Check the column for ordering
        if ($column === 'transaction_date') {
            $damageItems->orderBy('damages.transaction_date', $order);
        } elseif ($column === 'quantity') {
            $damageItems->orderBy('transfer_details.quantity', $order);
        }

        if($request->query('report') == "True")
        {
            $results = $damageItems->get();
            $resourceCollection = new DamageItemListResource($results, true);

            return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);
        }

        $damageItemsPaginated = $damageItems->paginate($perPage);

        $result = new DamageItemListResource($damageItemsPaginated);


        return $this->sendSuccessResponse('success', Response::HTTP_OK, $result);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DamageRequest $request)
    {
        try {
            DB::beginTransaction();
            $createdDamage = new Damage();
            $createdDamage->branch_id = Auth::user()->branch_id;
            $createdDamage->remark = $request->remark;
            $createdDamage->total_quantity = collect($request->item_details)->sum('quantity');
            $createdDamage->save();
            // array_sum(array_column($request->item_detail, 'quantity'))

            //create Transaction Detail 
            $createdTransactionDetail = $this->createTransactionDetail($request->item_details, $createdDamage->voucher_no);
            //deduct from branch's inventory
            $this->deductItemFromBranch($request->item_details, Auth::user()->branch_id);

            activity()
            ->useLog('DAMAGE')
            ->causedBy(Auth::user())
            ->event('created')
            ->performedOn($createdDamage)
            // ->withProperties(['Reveieve' => $createdDamage , 'ReceiveDetail' => $createdTransactionDetail])
            ->log('{userName} created the Damage (Voucher_no)'.$createdDamage->voucher_no.')');


            DB::commit();
            $message = 'Damage (' . $createdDamage->voucher_no . ') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            info($e->getMessage());
            return $this->sendErrorResponse('Something went wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $damage = Damage::with('transfer_details')->findOrFail($id);

        $resourceCollection = new DamageDetailResource($damage);
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DamageRequest $request, string $id)
    {
        $updateDamage = Damage::with('transfer_details')->findOrFail($id);
        try {
            DB::beginTransaction();
            $updateDamage->remark = $request->remark;
            $updateDamage->total_quantity = collect($request->item_detail)->sum('quantity');
            $updateDamage->update();
            // array_sum(array_column($request->item_detail, 'quantity'))
            //create Transaction Detail 
            $createdTransferDetail = $this->updatedDamageDetail($request->item_details, $updateDamage->voucher_no, $updateDamage->branch_id);

            activity()
            ->useLog('DAMAGE')
            ->causedBy(Auth::user())
            ->setEvent('updated')
            ->performedOn($updateDamage)
            // ->withProperties(['Reveieve' => $updateDamage , 'ReceiveDetail' => $createdTransferDetail])
            ->log('{userName} updated the Damage (Voucher_no'.$updateDamage->voucher_no.')');


            DB::commit();
            $message = 'Damage voucher (' . $updateDamage->voucher_no . ') is updated successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            info($e->getMessage());
            return $this->sendErrorResponse('Something went wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $damage = Damage::findOrFail($id);
        try {
            DB::beginTransaction();

            activity()
            ->useLog('DAMAGE')
            ->causedBy(Auth::user())
            ->setEvent('deleted')
            ->performedOn($damage)
            // ->withProperties(['Reveieve' => $damage , 'ReceiveDetail' => $damage->transfer_details])
            ->log('{userName} deleted the Damage (Voucher_no -'.$damage->voucher_no.')');

            $deleteTransactionDetail = $this->deleteDamageDetailAndIncInventory($damage->voucher_no, $damage->branch_id);
            $damage->delete();
            DB::commit();

            $message = 'Damage voucher (' . $damage->voucher_no . ') is deleted successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            info($e->getMessage());
            return $this->sendErrorResponse('Error deleting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
