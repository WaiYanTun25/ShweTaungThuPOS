<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesRequest;
use App\Http\Resources\SalesDetailResource;
use App\Http\Resources\SalesListResource;
use App\Models\Sale;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Traits\{
    SalesTrait,
    TransactionTrait
};
use stdClass;

class SalesController extends ApiBaseController
{
    use SalesTrait, TransactionTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
         // အဝယ်မှတ်တမ်း
         $getSales = Sale::with(['sales_details', 'customer']);

         try {
             $search = $request->query('searchBy');
             if ($search) {
                 $getSales->where(function ($query) use ($search) {
                     $query->where('voucher_no', 'like', "%$search%")
                         ->orWhereHas('customer', function ($supplierQuery) use ($search) {
                             $supplierQuery->where('name', 'like', "%$search%");
                         })
                         ->orWhereHas('sales_details', function ($detailsQuery) use ($search) {
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
                 // $getSales->whereBetween('purchase_date', [$startDate, $endDate]);
                 $getSales->whereDate('sales_date', '>=', $startDate)
                 ->whereDate('sales_date', '<=', $endDate);
             }
 
             // Supplier Filtering
             $customer_id = $request->query('customer_id');
             if ($customer_id) {
                 $getSales->where('customer_id', $customer_id);
             }
             // payment Filtering
             $payment = $request->query('payment');
             if ($payment) {
                 $getSales->where('payment_status', $payment);
             }
 
             // Handle order and column
             $order = $request->query('order', 'desc'); // default to asc if not provided
             $column = $request->query('column', 'purchase_date'); // default to id if not provided
             $perPage = $request->query('perPage', 10);
 
             $result = new stdClass;
             if($request->query('report') == "True"){
                 $result->data = $getSales->orderBy($column, $order)->get();
                 // this true is always true cause
                 // i use this resource function in two place 
                 // this controller funciton is always true
                 $resourceCollection = new SalesListResource($result, true, true);
                 return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
             }else{
                 $result->data = $getSales->orderBy($column, $order)->paginate($perPage);
             }
 
             $resourceCollection = new SalesListResource($result, true);
 
             return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
         } catch (Exception $e) {
             info($e->getMessage());
             return $this->sendErrorResponse('Error getting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
         }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SalesRequest $request)
    {
        // Validate the purchase request data
        $validatedData = $request->validated();
        $branch_id = Auth::user()->branch_id;
        DB::beginTransaction();
        try{
            $createdSales = $this->createOrUpdateSales($validatedData, $branch_id);

            $createdSales->sales_details()->createMany($validatedData['sales_details']);

            // Add the items to the branch
            $this->deductItemFromBranch($validatedData['sales_details'], $branch_id , true);

            // Commit the database transaction
            DB::commit();

            $message = 'Sale (' . $createdSales->voucher_no . ') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        }catch(Exception $e){
            DB::rollBack();
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $getSales = Sale::with('sales_details')->findOrFail($id);
        $result = new SalesDetailResource($getSales);

        return $this->sendSuccessResponse('Success', Response::HTTP_OK, $result);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SalesRequest $request, string $id)
    {
        $updatedSales = Sale::findOrFail($id);
        DB::beginTransaction();

        try{
            $validatedData = $request->validated();
            $this->createOrUpdateSales($validatedData, Auth::user()->branch_id, true, $updatedSales);

            $this->addItemToBranch($validatedData['sales_details']);

            // deduct prev quantity from branch
            $this->deductItemFromBranch($updatedSales->sales_details, Auth::user()->branch_id, true);

            DB::commit();
            $message = 'Puchases (' . $updatedSales->voucher_no . ') is updated successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        }catch(Exception $e){
            DB::rollBack();
            info($e->getMessage());
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    // addon
    public function getTotalSalesList(Request $request)
    {
        $getSales = Sale::with(['sales_details', 'customer']);

        try {
            $search = $request->query('searchBy');
            if ($search) {
                $getSales->where(function ($query) use ($search) {
                    $query->where('voucher_no', 'like', "%$search%")
                        ->orWhereHas('customer', function ($supplierQuery) use ($search) {
                            $supplierQuery->where('name', 'like', "%$search%");
                        })
                        ->orWhereHas('sales_details', function ($detailsQuery) use ($search) {
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
                // $getSales->whereBetween('purchase_date', [$startDate, $endDate]);
                $getSales->whereDate('sales_date', '>=', $startDate)
                ->whereDate('sales_date', '<=', $endDate);
            }

            // Supplier Filtering
            $customer_id = $request->query('customer_id');
            if ($customer_id) {
                $getSales->where('customer_id', $customer_id);
            }

            $total_sales_amount = $getSales->sum('total_amount');
            $total_pay_amount = $getSales->sum('pay_amount');
            $total_remain_amount = $getSales->sum('remain_amount');

            // Handle order and column
            $order = $request->query('order', 'desc'); // default to asc if not provided
            $column = $request->query('column', 'sales_date'); // default to id if not provided
            $perPage = $request->query('perPage', 10);

            $result = new stdClass;

            $result->total_sales_amount = $total_sales_amount;
            $result->total_pay_amount = $total_pay_amount;
            $result->total_remain_amount = $total_remain_amount;
            if($request->query('report') == "True"){
                $result->data = $getSales->orderBy($column, $order)->get();
                // this true is always true cause
                // i use this resource function in two place 
                // this controller funciton is always true
                $resourceCollection = new SalesListResource($result, false, true);
                return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
            }else{
                $result->data = $getSales->orderBy($column, $order)->paginate($perPage);
            }

            $resourceCollection = new SalesListResource($result);

            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
        } catch (Exception $e) {
            info($e->getMessage());
            return $this->sendErrorResponse('Error getting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
