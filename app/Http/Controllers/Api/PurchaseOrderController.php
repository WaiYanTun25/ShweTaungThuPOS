<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderListResource;
use App\Models\PurchaseOrder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Traits\PurchaseOrderTrait;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Facades\LogBatch;
use Spatie\Activitylog\Models\Activity;
use stdClass;

class PurchaseOrderController extends ApiBaseController
{
    use PurchaseOrderTrait;

    public function index(Request $request)
    {
        $getPurhcaseOrders = PurchaseOrder::with('purchase_order_details');
        try{
            $search = $request->query('searchBy');
            if ($search) {
                $getPurhcaseOrders->where(function ($query) use ($search) {
                    $query->where('voucher_no', 'like', "%$search%")
                        ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                            $supplierQuery->where('name', 'like', "%$search%");
                        })
                        ->orWhereHas('purchase_order_details', function ($detailsQuery) use ($search) {
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
                $getPurhcaseOrders->whereBetween('order_date', [$startDate, $endDate]);
            }

            $supplierId = $request->query('supplierId');
            if ($supplierId) {
                $getPurhcaseOrders->where('supplier_id', $supplierId);
            }

            // Handle order and column
            $order = $request->query('order', 'desc'); // default to asc if not provided
            $column = $request->query('column', 'purchase_date'); // default to id if not provided
            $perPage = $request->query('perPage', 10);

            $result = $getPurhcaseOrders->orderBy($column, $order)->paginate($perPage);

            $resourceCollection = new PurchaseOrderListResource($result);
            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
        }catch(Exception $e){
            // 'Error getting purchase order'
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }

    }
    public function create(PurchaseOrderRequest $request)
    {
        $validatedData = $request->validated();

        DB::beginTransaction();
        try {
            // LogBatch::startBatch();
            $createdPurchaseOrder = $this->createOrUpdatePurchaseOrder($validatedData, Auth::user()->branch_id);

            $createdPurchaseOrder->purchase_order_details()->createMany($validatedData['purchase_order_details']);
            DB::commit();
            // LogBatch::endBatch();

            $message = 'Purchase Order (' . $createdPurchaseOrder->voucher_no . ') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(PurchaseOrderRequest $request, string $id)
    {
        $updatePurchaseOrder = PurchaseOrder::findOrFail($id);
        DB::beginTransaction();
        try {
            // LogBatch::startBatch();
            $validatedData = $request->validated();
            // update the purchase
            $this->createOrUpdatePurchaseOrder($validatedData, Auth::user()->branch_id, true, $updatePurchaseOrder);
            // delete prev purchase order details
            $updatePurchaseOrder->purchase_order_details()->delete();
            // create new purchase order details
            $updatePurchaseOrder->purchase_order_details()->createMany($validatedData['purchase_order_details']);
            DB::commit();
            // LogBatch::endBatch();
            $message = 'Purchase Order (' . $updatePurchaseOrder->voucher_no . ') is updated successfully';
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
        $deletePurchaseOrder = PurchaseOrder::findOrFail($id);

        // Start a database transaction
        DB::beginTransaction();

        try {
            // LogBatch::startBatch();
            // Delete the purchase order details
            $deletePurchaseOrder->purchase_order_details()->delete();

            // foreach ($deletePurchaseOrder->purchase_order_details as $detail) {
            //     $detail->delete();
            // }
            // Delete the purchase order
            $deletePurchaseOrder->delete();

            // Commit the transaction
            DB::commit();
            // LogBatch::endBatch();
            // Build the success message
            $message = 'Purchase Order (' . $deletePurchaseOrder->voucher_no . ') is deleted successfully';

            // Return the success response
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            info($e->getMessage());
            return $this->sendErrorResponse('Error deleting Purchase Order', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}

// $purchaseOrder = PurchaseOrder::with('purchase_order_details')->find(7);

        // $purchase_order_activity = Activity::inLog('PURCHASE_ORDER')
        // ->where('subject_id', 7)
        // ->first();

        // if ($purchase_order_activity) {
        //     $batchUuid = $purchase_order_activity->batch_uuid;

        //     $purchaseDetailsActivities = Activity::inLog('PURCHASE_ORDER_DETAIL')
        //         ->forBatch($batchUuid)
        //         ->get();    
        // }

        // $result = new stdClass;
        // $result->purchase_order = $purchase_order_activity;
        // $result->purchase_order_details = $purchaseDetailsActivities;

        // return $result;