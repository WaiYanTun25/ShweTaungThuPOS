<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DamageRequest;
use App\Http\Resources\DamageResourceCollection;
use App\Models\Damage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Traits\TransactionTrait;

class DamageController extends ApiBaseController
{
    use TransactionTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $getTransfer = Damage::with(['transfer_details', 'transfer_details.unit', 'transfer_details.item'])->select('*');
        $search = $request->query('searchBy');

        if ($search) {
            $getTransfer->where('voucher_no', 'like', "%$search%")
                ->orWhere('transaction_date', 'like', "%$search%")
                ->orWhere('total_quantity', 'like', "%$search%");
        }

        // Handle order and column
        $order = $request->query('order', 'asc'); // default to asc if not provided
        $column = $request->query('column', 'id'); // default to id if not provided

        $getTransfer->orderBy($column, $order);

        // Add pagination
        $perPage = $request->query('perPage', 10); // default to 10 if not provided
        $transfers = $getTransfer->paginate($perPage);

        $resourceCollection = new DamageResourceCollection($transfers);

        // return $resourceCollection;
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DamageRequest $request)
    {
        try {
            DB::beginTransaction();
            $createdDamage = new Damage();
            $createdDamage->branch_id = $request->branch_id;
            $createdDamage->total_quantity =  collect($request->item_detail)->sum('quantity');
            $createdDamage->save();

            // array_sum(array_column($request->item_detail, 'quantity'))

            //create Transaction Detail 
            $createdIssueDetail = $this->createTransactionDetail($request->item_detail, $createdDamage->voucher_no);
            $deductItemFromBranch = $this->deductDamageItemFromBranch($request->item_detail, $request->branch_id);

            DB::commit();
            $message = 'Damage is created successfully';
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

        $resourceCollection = new DamageResourceCollection(collect([$damage]));
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection[0]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $updateDamage = Damage::with('transfer_details')->findOrFail($id);

        try {
            DB::beginTransaction();
            $updateDamage->total_quantity = collect($request->item_detail)->sum('quantity');
            $updateDamage->save();
            // array_sum(array_column($request->item_detail, 'quantity'))
            //create Transaction Detail 
            $createdTransferDetail = $this->updatedDamageDetail($request->item_detail, $updateDamage->voucher_no, $updateDamage->branch_id);

            DB::commit();
            $message = 'Damage voucher ('.$updateDamage->voucher_no.') is updated successfully';
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
        try{
            DB::beginTransaction();
            $deleteTransactionDetail = $this->deleteDamageDetailAndIncInventory($damage->voucher_no, $damage->branch_id);
            $damage->delete();
            DB::commit();

            $message = 'Damage voucher ('.$damage->voucher_no.') is deleted successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        }catch(Exception $e){
            DB::rollBack();
            info($e->getMessage());
            return $this->sendErrorResponse('Error deleting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
