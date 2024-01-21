<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesReturnListResource;
use App\Models\SalesReturn;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use stdClass;

class SalesReturnController extends ApiBaseController
{
    public function index(Request $request)
    {
        $getPurchaseReturn = SalesReturn::with('sales_return_details');

        try {
            $search = $request->query('searchBy');
            if ($search) {
                $getPurchaseReturn->where(function ($query) use ($search) {
                    $query->where('voucher_no', 'like', "%$search%")
                        ->orWhereHas('customer', function ($supplierQuery) use ($search) {
                            $supplierQuery->where('name', 'like', "%$search%");
                        })
                        ->orWhereHas('sales_return_details', function ($detailsQuery) use ($search) {
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
               $getPurchaseReturn->whereDate('sales_return_date', '>=', $startDate)
                ->whereDate('sales_return_date', '<=', $endDate);
            }

            // Supplier Filtering
            $customer_id = $request->query('customer_id');
            if ($customer_id) {
                $getPurchaseReturn->where('customer_id', $customer_id);
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
                $resourceCollection = new SalesReturnListResource($result, true);
                return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
            }else{
                $result->data = $getPurchaseReturn->orderBy($column, $order)->paginate($perPage);
            }

            $resourceCollection = new SalesReturnListResource($result);

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
}
