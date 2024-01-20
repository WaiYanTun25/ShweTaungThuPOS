<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseReturnRequest;
use App\Http\Resources\PurchaseReturnListResource;
use App\Models\PurchaseReturn;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\PurchaseReturnTrait;
use App\Traits\TransactionTrait;
use App\Traits\PaymentTrait;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use stdClass;

class PurchaseReturnController extends ApiBaseController
{
    use PurchaseReturnTrait, TransactionTrait, PaymentTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $getPurchaseReturn = PurchaseReturn::with(['purchase_return_details', 'supplier'])->get();
        return $getPurchaseReturn->purchase_return_details()->sum('total_amount');

        try {
            $search = $request->query('searchBy');
            if ($search) {
                $getPurchaseReturn->where(function ($query) use ($search) {
                    $query->where('voucher_no', 'like', "%$search%")
                        ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                            $supplierQuery->where('name', 'like', "%$search%");
                        })
                        ->orWhereHas('purchase_return_details', function ($detailsQuery) use ($search) {
                            $detailsQuery->whereHas('item', function ($itemQuery) use ($search) {
                                $itemQuery->where('item_name', 'like', "%$search%");
                            });
                        });
                });
            }


            // Date Filtering
            $startDate = $request->query('startDate');
            $endDate = $request->query('endDate');

            if ($startDate && $endDate) {
                // $getPurchaseReturn->whereBetween('purchase_date', [$startDate, $endDate]);
               $getPurchaseReturn->whereDate('purchase_date', '>=', $startDate)
                ->whereDate('purchase_date', '<=', $endDate);
            }

            // Supplier Filtering
            $supplierId = $request->query('supplier_id');
            if ($supplierId) {
                $getPurchaseReturn->where('supplier_id', $supplierId);
            }
            // payment Filtering
            $payment = $request->query('payment');
            if ($payment) {
                $getPurchaseReturn->where('payment_status', $payment);
            }

            // Handle order and column
            $order = $request->query('order', 'desc'); // default to asc if not provided
            $column = $request->query('column', 'purchase_date'); // default to id if not provided
            $perPage = $request->query('perPage', 10);

            $result = new stdClass;
            if($request->query('report') == "True"){
                $result->data = $getPurchaseReturn->orderBy($column, $order)->get();
                // this true is always true cause
                // use this resource function in two place 
                // this controller funciton is always true
                $resourceCollection = new PurchaseReturnListResource($result, true);
                return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
            }else{
                $result->data = $getPurchaseReturn->orderBy($column, $order)->paginate($perPage);
            }

            $resourceCollection = new PurchaseReturnListResource($result, true);

            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
        } catch (Exception $e) {
            info($e->getMessage());
            return $this->sendErrorResponse('Error getting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PurchaseReturnRequest $request)
    {
        $validatedData = $request->validated();
        DB::beginTransaction();
        try{
            // Create the purchase return
            $createdPurchaseReturn = $this->createOrUpdatePurchaseReturn($validatedData, Auth::user()->branch_id);

            $createdPurchaseReturn->purchase_return_details()->createMany($validatedData['purchase_return_details']);
            $this->deductItemFromBranch($validatedData['purchase_return_details'], Auth::user()->branch_id);
            DB::commit();

            $message = 'Purchase Return (' . $createdPurchaseReturn->voucher_no . ') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
         }catch(Exception $e){
            DB::rollBack();
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return $id;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
