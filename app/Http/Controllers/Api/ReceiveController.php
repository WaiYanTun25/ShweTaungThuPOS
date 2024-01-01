<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReceiveRequest;
use App\Http\Resources\IssueReceiveDetailResource;
use App\Models\Inventory;
use Illuminate\Http\Request;
use App\Http\Resources\TransferResourceCollection;
use App\Models\Receive;
use App\Models\TransferDetail;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Traits\TransactionTrait;
use Illuminate\Support\Facades\Auth;

class ReceiveController extends ApiBaseController
{
    use TransactionTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $getReceive = Receive::with(['transfer_details', 'transfer_details.unit', 'transfer_details.item'])->select('*');
        $search = $request->query('searchBy');

        if ($search) {
            $getReceive->where('voucher_no', 'like', "%$search%")
                ->orWhere('transaction_date', 'like', "%$search%")
                ->orWhere('total_quantity', 'like', "%$search%");
        }

        // Handle order and column
        $order = $request->query('order', 'asc'); // default to asc if not provided
        $column = $request->query('column', 'id'); // default to id if not provided

        $getReceive->orderBy($column, $order);

        // Add pagination
        $perPage = $request->query('perPage', 10); // default to 10 if not provided
        $transfers = $getReceive->paginate($perPage);

        $resourceCollection = new TransferResourceCollection($transfers);

        // return $resourceCollection;
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ReceiveRequest $request)
    {
        try {
            DB::beginTransaction();

            $createdReceive = new Receive();
            $createdReceive->from_branch_id = $request->from_branch_id;
            $createdReceive->to_branch_id = Auth::user()->branch_id;
            $createdReceive->total_quantity =  collect($request->item_details)->sum('quantity');
            $createdReceive->save();
            // array_sum(array_column($request->item_detail, 'quantity'))

            //create Transaction Detail 
            $createdReceiveDetail = $this->createTransactionDetail($request->item_details, $createdReceive->voucher_no);

            DB::commit();
            $message = 'Receive ('.$createdReceive->voucher_no.') is created successfully';
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
        $receive = Receive::with('transfer_details')->findOrFail($id);
        // return $receive;
        $result =  new IssueReceiveDetailResource($receive, 'RECEIVE');
        // $resourceCollection = new TransferResourceCollection(collect([$receive]));
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $result);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ReceiveRequest $request, string $id)
    {
        $receive = Receive::with('transfer_details')->findOrFail($id);

        try{
            DB::beginTransaction();
            $this->deductItemFromBranch($receive->transfer_details, $receive->to_branch_id);
            TransferDetail::where('voucher_no', $receive->voucher_no)->delete();
            //create Transaction Detail 
             $this->createTransactionDetail($request->item_details, $receive->voucher_no);
             $this->addItemtoBranch($request->item_details, $receive->voucher_no);
            $receive->from_branch_id = $request->from_branch_id;
            $receive->save();
            DB::commit();
            $message = 'Voucher Number ('.$receive->voucher_no.") is updated.";
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        }catch(Exception $e){
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
        $issue = Receive::with('transfer_details')->findOrFail($id);
        try{
            DB::beginTransaction();
            $deleteTransactionDetail = $this->deductItemFromBranch($issue->transfer_details, $issue->to_branch_id);
            $issue->transfer_details()->delete();
            $issue->delete();
            DB::commit();

            $message = 'Issue ('.$issue->voucher_no.') is deleted successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        }catch(Exception $e){
            DB::rollBack();
            info($e->getMessage());
            return $this->sendErrorResponse('Error deleting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
