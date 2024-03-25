<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderDetailResource;
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

    public function __construct()
    {
        $checkPermission = request()->query('permission') === 'True';
        // Conditionally apply permission middleware
        if ($checkPermission) {
            $this->middleware('permission:purchases:read')->only('index', 'detail');
            $this->middleware('permission:purchases:create')->only('create');
            $this->middleware('permission:purchases:edit')->only('update');
            $this->middleware('permission:purchases:delete')->only('delete');
        }
    }

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
                // $getPurhcaseOrders->whereBetween('order_date', [$startDate, $endDate]);
                $getPurhcaseOrders->whereDate('order_date', '>=', $startDate)
                ->whereDate('order_date', '<=', $endDate);
            }

            $supplierId = $request->query('supplierId');
            if ($supplierId) {
                $getPurhcaseOrders->where('supplier_id', $supplierId);
            }

            // Handle order and column
            $order = $request->query('order', 'desc'); // default to asc if not provided
            $column = $request->query('column', 'purchase_date'); // default to id if not provided
            $perPage = $request->query('perPage', 10);

            if($request->query('report') == "True")
            {
                $result = $getPurhcaseOrders->orderBy($column, $order)->get();
                $resourceCollection = new PurchaseOrderListResource($result, True);
                return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
            }
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

            activity()
            ->useLog('PURCHASE_ORDER')
            ->causedBy(Auth::user())
            ->event('created')
            ->performedOn($createdPurchaseOrder)
            // ->withProperties(['Reveieve' => $createdDamage , 'ReceiveDetail' => $createdTransactionDetail])
            ->log('{userName} created the Purchase Order (Voucher_no)'.$createdPurchaseOrder->voucher_no.')');

            $message = 'Purchase Order (' . $createdPurchaseOrder->voucher_no . ') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function detail($id)
    {
        $purchase_order = PurchaseOrder::with('purchase_order_details')->findOrFail($id);
        $result = new PurchaseOrderDetailResource($purchase_order);

        return $this->sendSuccessResponse('Success', Response::HTTP_OK, $result);
    }

    public function update(PurchaseOrderRequest $request, string $id)
    {
        $updatePurchaseOrder = PurchaseOrder::findOrFail($id);
        DB::beginTransaction();
        try {
            // LogBatch::startBatch();
            $validatedData = $request->validated();
            // update the purchase
            // $this->createOrUpdatePurchaseOrder($validatedData, Auth::user()->branch_id, true, $updatePurchaseOrder);
            $updatedPurchaseOrder = $this->createOrUpdatePurchaseOrder($validatedData, Auth::user()->branch_id, true, $updatePurchaseOrder);
            // delete prev purchase order details
            $updatePurchaseOrder->purchase_order_details()->delete();
            // create new purchase order details
            $updatePurchaseOrder->purchase_order_details()->createMany($validatedData['purchase_order_details']);
            DB::commit();

            activity()
            ->useLog('PURCHASE_ORDER')
            ->causedBy(Auth::user())
            ->event('updated')
            ->performedOn($updatedPurchaseOrder)
            // ->withProperties(['Reveieve' => $createdDamage , 'ReceiveDetail' => $createdTransactionDetail])
            ->log('{userName} updated the Purchase Order (Voucher_no)'.$updatedPurchaseOrder->voucher_no.')');

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

            activity()
            ->useLog('PURCHASE_ORDER')
            ->causedBy(Auth::user())
            ->event('deleted')
            ->performedOn($deletePurchaseOrder)
            // ->withProperties(['Reveieve' => $createdDamage , 'ReceiveDetail' => $createdTransactionDetail])
            ->log('{userName} deleted the Purchase Order (Voucher_no)'.$deletePurchaseOrder->voucher_no.')');

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