<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesOrderRequest;
use App\Http\Resources\SalesOrderDetailResource;
use App\Http\Resources\SalesOrderListResource;
use App\Models\SalesOrder;
use Illuminate\Http\Request;

use App\Traits\SalesOrderTrait;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends ApiBaseController
{
    use SalesOrderTrait;

    public function __construct()
    {
        $checkPermission = request()->query('permission') === 'True';
        $this->middleware('check.branch')->only('create', 'update');
        // Conditionally apply permission middleware
        if ($checkPermission) {
            $this->middleware('permission:sales:read')->only('index', 'detail');
            $this->middleware('permission:sales:create')->only('create');
            $this->middleware('permission:sales:edit')->only('update');
            $this->middleware('permission:sales:delete')->only('delete');
        }
    }
    public function index(Request $request)
    {
        $getSalesOrders = SalesOrder::with('sales_order_details');
        try{
            $search = $request->query('searchBy');
            if ($search) {
                $getSalesOrders->where(function ($query) use ($search) {
                    $query->where('voucher_no', 'like', "%$search%")
                        ->orWhereHas('customer', function ($supplierQuery) use ($search) {
                            $supplierQuery->where('name', 'like', "%$search%");
                        })
                        ->orWhereHas('sales_order_details', function ($detailsQuery) use ($search) {
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
                // $getSalesOrders->whereBetween('order_date', [$startDate, $endDate]);
                $getSalesOrders->whereDate('order_date', '>=', $startDate)
                ->whereDate('order_date', '<=', $endDate);
            }

            $customerId = $request->query('customer_id');
            if ($customerId) {
                $getSalesOrders->where('customer_id', $customerId);
            }

            // Handle order and column
            $order = $request->query('order', 'desc'); // default to asc if not provided
            $column = $request->query('column', 'purchase_date'); // default to id if not provided
            $perPage = $request->query('perPage', 10);

            if($request->query('report') == "True")
            {
                $result = $getSalesOrders->orderBy($column, $order)->get();
                $resourceCollection = new SalesOrderListResource($result, True);
                return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
            }
            $result = $getSalesOrders->orderBy($column, $order)->paginate($perPage);

            $resourceCollection = new SalesOrderListResource($result);
            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
        }catch(Exception $e){
            // 'Error getting purchase order'
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }

    }
    public function create(SalesOrderRequest $request)
    {
        $validatedData = $request->validated();
        DB::beginTransaction();
        try {
            // LogBatch::startBatch();
            $createdPurchaseOrder = $this->createOrUpdateSalesOrder($validatedData, Auth::user()->branch_id);

            $createdPurchaseOrder->sales_order_details()->createMany($validatedData['sales_order_details']);
            DB::commit();
            // LogBatch::endBatch();

            $message = 'Sales Order (' . $createdPurchaseOrder->voucher_no . ') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function detail($id)
    {
        $sales_order = SalesOrder::with('Sales_order_details')->findOrFail($id);
        $result = new SalesOrderDetailResource($sales_order);

        return $this->sendSuccessResponse('Success', Response::HTTP_OK, $result);
    }

    public function update(SalesOrderRequest $request, string $id)
    {
        $updatedSalesOrder = SalesOrder::findOrFail($id);
        DB::beginTransaction();
        try {
            // LogBatch::startBatch();
            $validatedData = $request->validated();
            // update the purchase
            $this->createOrUpdateSalesOrder($validatedData, Auth::user()->branch_id, true, $updatedSalesOrder);
            // delete prev purchase order details
            $updatedSalesOrder->sales_order_details()->delete();
            // create new purchase order details
            $updatedSalesOrder->sales_order_details()->createMany($validatedData['sales_order_details']);
            DB::commit();
            // LogBatch::endBatch();
            $message = 'Sales Order (' . $updatedSalesOrder->voucher_no . ') is updated successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            info($e->getMessage());
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a purchase order by ID.
     *
     * @param string $id The ID of the purchase order to delete.
     * @return \Illuminate\Http\Response The response indicating the success or failure of the operation.
     */
    public function delete(string $id)
    {
        // Find the purchase order by ID
        $deleteSalesOrder = SalesOrder::findOrFail($id);

        // Start a database transaction
        DB::beginTransaction();

        try {
            // LogBatch::startBatch();
            // Delete the purchase order details
            $deleteSalesOrder->sales_order_details()->delete();

            // foreach ($deleteSalesOrder->purchase_order_details as $detail) {
            //     $detail->delete();
            // }
            // Delete the purchase order
            $deleteSalesOrder->delete();

            // Commit the transaction
            DB::commit();
            // LogBatch::endBatch();
            // Build the success message
            $message = 'Sales Order (' . $deleteSalesOrder->voucher_no . ') is deleted successfully';

            // Return the success response
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            info($e->getMessage());
            return $this->sendErrorResponse('Error deleting Purchase Order', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
