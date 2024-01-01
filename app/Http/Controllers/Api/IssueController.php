<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRequest;
use App\Http\Resources\IssueReceiveDetailResource;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Traits\TransactionTrait;
use App\Http\Resources\TransferResourceCollection;
use App\Models\Damage;
use App\Models\Issue;
use App\Models\Receive;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Transfer;
use Illuminate\Support\Facades\Auth;
use stdClass;

class IssueController extends ApiBaseController
{
    use TransactionTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $getTransfer = Issue::with(['transfer_details', 'transfer_details.unit', 'transfer_details.item'])->select('*');
        // $search = $request->query('searchBy');

        // if ($search) {
        //     $getTransfer->where('voucher_no', 'like', "%$search%")
        //         ->orWhere('transaction_date', 'like', "%$search%")
        //         ->orWhere('total_quantity', 'like', "%$search%");
        // }

        // // Handle order and column
        // $order = $request->query('order', 'asc'); // default to asc if not provided
        // $column = $request->query('column', 'id'); // default to id if not provided

        // $getTransfer->orderBy($column, $order);

        // // Add pagination
        // $perPage = $request->query('perPage', 10); // default to 10 if not provided
        // $transfers = $getTransfer->paginate($perPage);

        // $resourceCollection = new TransferResourceCollection($transfers);

        // // return $resourceCollection;
        // return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransferRequest $request)
    {
        try {
            DB::beginTransaction();

            $createdIssue = new Issue();
            $createdIssue->from_branch_id = Auth::user()->branch_id;
            $createdIssue->to_branch_id = $request->to_branch_id;
            $createdIssue->total_quantity =  collect($request->item_details)->sum('quantity');
            $createdIssue->save();
            // array_sum(array_column($request->item_detail, 'quantity'))

            //create Transaction Detail 
            $createdIssueDetail = $this->createTransactionDetail($request->item_details, $createdIssue->voucher_no);

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
        $issue = Issue::with('transfer_details')->findOrFail($id);
        // return $receive;
        $result =  new IssueReceiveDetailResource($issue);
        // $resourceCollection = new TransferResourceCollection(collect([$receive]));
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $result);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransferRequest $request, string $id)
    {
        $updateTransfer = Issue::findOrFail($id);
        try {
            DB::beginTransaction();
            $updateTransfer->total_quantity = collect($request->item_details)->sum('quantity');
            $updateTransfer->to_branch_id = $request->to_branch_id;
            $updateTransfer->save();
            // array_sum(array_column($request->item_detail, 'quantity'))
            //create Transaction Detail 
            $createdTransferDetail = $this->updatedTransactionDetail($request->item_details, $updateTransfer->voucher_no, $updateTransfer->from_branch_id);

            DB::commit();
            $message = 'Issue ('.$updateTransfer->voucher_no.') is updated successfully';
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
        $issue = Issue::findOrFail($id);
        try{
            DB::beginTransaction();
            $deleteTransactionDetail = $this->deleteTransactionDetail($issue->voucher_no, $issue->from_branch_id);
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

    // custom functions
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

        // $getDamage = Damage::with(['transfer_details', 'transfer_details.unit', 'transfer_details.item'])
        //     ->select(['id', 'voucher_no', 'total_quantity', 'transaction_date', DB::raw("branch_id as branch_id"), DB::raw("'Damage' as type")]);
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
            
            // $getDamage->where('voucher_no', 'like', "%$search%")
            //     ->orWhere('transaction_date', 'like', "%$search%")
            //     ->orWhere('total_quantity', 'like', "%$search%");
        }

        // Handle order and column
        $order = $request->query('order', 'asc'); // default to asc if not provided
        $column = $request->query('column', 'transaction_date'); // default to id if not provided
        // Combine the results using union
        $perPage = $request->query('perPage', 10); 
        $results = $getIssue->union($getReceive)->orderBy($column, $order)->paginate($perPage);
        // $results = $getIssue->union($getReceive)->union($getDamage)->orderBy($column, $order)->paginate($perPage);
        // return $results;
        $resourceCollection = new TransferResourceCollection($results);
        // return $results;

        // return $resourceCollection;
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
}
