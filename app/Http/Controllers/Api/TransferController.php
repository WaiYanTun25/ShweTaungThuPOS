<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Traits\TransactionTrait;
use App\Http\Resources\TransferResourceCollection;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Transfer;

class TransferController extends ApiBaseController
{
    use TransactionTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $getTransfer = Transfer::with(['transfer_details', 'transfer_details.unit', 'transfer_details.item'])->select('*');
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

        $resourceCollection = new TransferResourceCollection($transfers);

        // return $resourceCollection;
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransferRequest $request)
    {
        try {
            DB::beginTransaction();
            $createdIssue = new Transfer();
            $createdIssue->from_branch_id = $request->from_branch_id;
            $createdIssue->to_branch_id = $request->to_branch_id;
            $createdIssue->total_quantity =  collect($request->item_detail)->sum('quantity');
            $createdIssue->save();
            // array_sum(array_column($request->item_detail, 'quantity'))

            //create Transaction Detail 
            $createdIssueDetail = $this->createTransactionDetail($request->item_detail, $createdIssue->voucher_no);

            DB::commit();
            $message = 'Issue is created successfully';
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
        $transfer = Transfer::with('transfer_details')->findOrFail($id);

        $resourceCollection = new TransferResourceCollection(collect([$transfer]));
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection[0]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransferRequest $request, string $id)
    {
        $updateTransfer = Transfer::findOrFail($id);

        // Check if the status is received and prevent update
        if ($updateTransfer->status === Transfer::RECEIVE) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Cannot update a transfer that has already been received.',
                    'errors' => [], // You can provide additional error details if needed
                ], Response::HTTP_UNPROCESSABLE_ENTITY)
            );
        }

        try {
            DB::beginTransaction();
            $updateTransfer->total_quantity = collect($request->item_detail)->sum('quantity');
            $updateTransfer->save();
            // array_sum(array_column($request->item_detail, 'quantity'))
            //create Transaction Detail 
            $createdTransferDetail = $this->updatedTransactionDetail($request->item_detail, $updateTransfer->voucher_no);

            DB::commit();
            $message = 'Issue is updated successfully';
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
        $issue = Transfer::findOrFail($id);
        if($issue->status == Transfer::RECEIVE) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Cannot update a transfer that has already been received.',
                    'errors' => [], // You can provide additional error details if needed
                ], Response::HTTP_UNPROCESSABLE_ENTITY)
            );
        };
        try{
            DB::beginTransaction();
            $deleteTransactionDetail = $this->deleteTransactionDetail($issue->voucher_no);
            $issue->delete();
            DB::commit();

            $message = 'Issue is deleted successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        }catch(Exception $e){
            DB::rollBack();
            info($e->getMessage());
            return $this->sendErrorResponse('Error deleting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
