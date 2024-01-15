<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRequest;
use App\Http\Resources\IssueReceiveDamageResource;
use App\Http\Resources\IssueReceiveDetailResource;
use App\Http\Resources\IssueReceiveResource;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Traits\TransactionTrait;
// use App\Http\Resources\IssueReceiveDamageResource;
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

            //create Transaction Detail 
            $createdIssueDetail = $this->createTransactionDetail($request->item_details, $createdIssue->voucher_no);

            DB::commit();

            activity()
            ->causedBy(Auth::user())
            ->event('created')
            ->performedOn($createdIssue)
            // ->withProperties(['Reveieve' => $createdIssue , 'ReceiveDetail' => $createdIssueDetail])
            ->log('{$userName} created the Issue (Voucher_no)'.$createdIssue->voucher_no.')');

            $message = 'Issue (' . $createdIssue->voucher_no . ') is created successfully';
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
            $updateTransfer->update();
            //create Transaction Detail 
            $createdTransactionDetail = $this->updatedTransactionDetail($request->item_details, $updateTransfer->voucher_no, $updateTransfer->from_branch_id);

            activity()
            ->causedBy(Auth::user())
            ->setEvent('updated')
            ->performedOn($updateTransfer)
            // ->withProperties(['Reveieve' => $updateTransfer , 'ReceiveDetail' => $createdTransactionDetail])
            ->log('{$userName} updated the Issue (Voucher_no'.$updateTransfer->voucher_no.')');

            
            DB::commit();
            $message = 'Issue (' . $updateTransfer->voucher_no . ') is updated successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            info($e->getMessage());
            return $this->sendErrorResponse('Error Updating Issue', Response::HTTP_INTERNAL_SERVER_ERROR);
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

            activity()
            ->causedBy(Auth::user())
            ->setEvent('deleted')
            ->performedOn($issue)
            // ->withProperties(['Reveieve' => $issue , 'ReceiveDetail' => $issue->transfer_details])
            ->log('{$userName} deleted the Issue (Voucher_no -'.$issue->voucher_no.')');

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
    public function getIssuesReceives(Request $request)
    {
        // filter query request
        $reason = $request->query('reason');
        $reasonArray = explode(",", $reason);

        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        // receiveFrom To
        $receiveFrom = $request->query('receiveFrom');
        $receiveTo = $request->query('receiveTo');

        // issueFrom To
        $issueFrom = $request->query('issueFrom');
        $issueTo = $request->query('issueTo');

        $withDateFilter = function ($query) use ($startDate, $endDate) {
            if ($startDate && $endDate) {
                $query->whereBetween('transaction_date', [$startDate, $endDate]);
            }
        };

        $getIssue = Issue::with(['transfer_details', 'transfer_details.unit', 'transfer_details.item'])
        ->select(['id', 'voucher_no', 'total_quantity', 'from_branch_id', 'to_branch_id', 'transaction_date', DB::raw("'ISSUE' as type")])
        ->when($issueFrom, function ($query) use ($issueFrom) {
            return $query->where('from_branch_id', $issueFrom);
        })
        ->when($issueTo, function ($query) use ($issueTo) {
            return $query->where('to_branch_id', $issueTo);
        })
        ->tap($withDateFilter);

        $getReceive = Receive::with(['transfer_details', 'transfer_details.unit', 'transfer_details.item'])
            ->select(['id', 'voucher_no', 'total_quantity', 'from_branch_id', 'to_branch_id', 'transaction_date', DB::raw("'RECEIVE' as type")])
            ->when($receiveFrom, function ($query) use ($receiveFrom) {
                return $query->where('from_branch_id', $receiveFrom);
            })
            ->when($receiveTo, function ($query) use ($receiveTo) {
                return $query->where('to_branch_id', $receiveTo);
            })
            ->tap($withDateFilter);

        $search = $request->query('searchBy');

        if ($search) {
            $getIssue->where('voucher_no', 'like', "%$search%")
                ->orWhereHas('transfer_details.item', function ($query) use ($search) {
                    $query->where('item_name', 'like', "%$search%");
                });

            $getReceive->where('voucher_no', 'like', "%$search%")
                // ->orWhere('transaction_date', 'like', "%$search%")
                // ->orWhere('total_quantity', 'like', "%$search%")
                ->orWhereHas('transfer_details.item', function ($query) use ($search) {
                    $query->where('item_name', 'like', "%$search%");
                });
        }

        if (in_array('ISSUE', $reasonArray) && in_array('RECEIVE', $reasonArray)) {
            $results = $getIssue->union($getReceive);
        } elseif (in_array('ISSUE', $reasonArray)) {
            $results = $getIssue;
        } elseif (in_array('RECEIVE', $reasonArray)) {
            $results = $getReceive;
        } else {
            // Default case when none of the conditions match
            $results = $getIssue->union($getReceive);
        }

        // Handle order and column
        $order = $request->query('order', 'asc'); // default to asc if not provided
        $column = $request->query('column', 'transaction_date'); // default to id if not provided
        $perPage = $request->query('perPage', 10); 

        $results = $results->orderBy($column, $order);

        if($request->query('report') == "True")
        {
            $results = $results->get();
            $resourceCollection = new IssueReceiveResource($results, true);

            return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);
        }

        $results = $results->paginate($perPage);

        // return $results;
        $resourceCollection = new IssueReceiveResource($results);

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);
    }
}
