<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use Illuminate\Http\Request;
use App\Http\Resources\TransferResourceCollection;
use App\Models\Receive;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Traits\TransactionTrait;

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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $receive = Receive::with('transfer_details')->findOrFail($id);

        $resourceCollection = new TransferResourceCollection(collect([$receive]));
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection[0]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $receive = Receive::with('transfer_details')->findOrFail($id);

        if ($receive->status === Receive::RECEIVE) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'This voucher number has already been received.',
                    'errors' => [], // You can provide additional error details if needed
                ], Response::HTTP_UNPROCESSABLE_ENTITY)
            );
        }
        try{
            DB::beginTransaction();
            $this->addOrCreateItemToInventories($receive);
            $receive->status = Receive::RECEIVE;
            $receive->save();
            DB::commit();
            $message = 'Voucher Number ('.$receive->voucher_no.") is receieved.";
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
        //
    }
}
