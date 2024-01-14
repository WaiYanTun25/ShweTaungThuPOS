<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\PurchaseRequest;
use App\Http\Resources\PrePurchaseReturnResource;
use App\Http\Resources\PurchaseDetailResource;
use App\Http\Resources\PurchasesListResource;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchaseReturn;
use Exception;
use Illuminate\Http\{
    Request,
    Response
};
use Illuminate\Support\Facades\DB;
use App\Traits\{
    PurchaseTrait,
    TransactionTrait,
    PaymentTrait,
};
use Illuminate\Support\Facades\Auth;
use stdClass;

class PurchaseController extends ApiBaseController
{
    use PurchaseTrait, TransactionTrait, PaymentTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return;
    }

    /**
     * Store a new purchase.
     *
     * @param PurchaseRequest $request The purchase request object.
     * @throws Exception If an error occurs while creating the purchase.
     * @return Response The response object.
     */
    public function store(PurchaseRequest $request)
    {
        
        // Validate the purchase request data
        $validatedData = $request->validated();
        // Begin a database transaction
        DB::beginTransaction();
        try {
            // Create a payment if the payment status is not 'UN_PAID'
            $created_payment = null;
            if ($validatedData['payment_status'] != 'UN_PAID') {
                $created_payment = $this->createPayment($validatedData);
            }
            
            // Create the purchase
            $createdPurchase = $this->createOrUpdatePurchase($created_payment?->id, $validatedData, Auth::user()->branch_id);

            // Create the purchase details
            // $createdDetails = $this->createPurchaseDetail($validatedData, $createdPurchase->id);
            $createdPurchase->purchase_details()->createMany($validatedData['purchase_details']);
            // Add the items to the branch
            $this->addItemToBranch($validatedData['purchase_details']);

            // Commit the database transaction
            DB::commit();

            // Return a success response
            $message = 'Purchase (' . $createdPurchase->voucher_no . ') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        } catch (Exception $e) {
            // Rollback the database transaction and return an error response
            DB::rollBack();
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $getPurchase = Purchase::with('purchase_details')->findOrFail($id);
        $result = new PurchaseDetailResource($getPurchase);

        return $this->sendSuccessResponse('Success', Response::HTTP_OK, $result);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PurchaseRequest $request, string $id)
    {
        $updatePurchase = Purchase::findOrFail($id);
        DB::beginTransaction();
        try {
            $validatedData = $request->validated();
            // delete prev payments
            $updatePurchase->payment()->delete();
            // create new payments
            $created_payment = null;
            if ($validatedData['payment_status'] != 'UN_PAID') {
                $created_payment = $this->createPayment($validatedData, $updatePurchase->id);
            }
            // update the purchase
            $this->createOrUpdatePurchase($created_payment->id, $validatedData, Auth::user()->branch_id, true, $updatePurchase);
            
            // deduct prev quantity from branch
            $this->deductItemFromBranch($updatePurchase->purchase_details, Auth::user()->branch_id);
            $updatePurchase->purchase_details()->delete();

            // create many purchase details
            $updatePurchase->purchase_details()->createMany($validatedData['purchase_details']);
            // Add the items to the branch
            $this->addItemToBranch($validatedData['purchase_details']);

            DB::commit();
            $message = 'Issue (' . $updatePurchase->voucher_no . ') is updated successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            info($e->getMessage());
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // $checkPurchaseReturn = PurchaseReturn::where('purchase_id', $id)->first();

        // if ($checkPurchaseReturn) {
        //     $message = 'This Purchase has Purchase Return data!';
        //     return $this->sendErrorResponse($message, Response::HTTP_BAD_REQUEST);
        // }
        
        $deletePurchase = Purchase::findOrFail($id);
        DB::beginTransaction();
        try {
            $this->addItemtoBranch($deletePurchase->purchase_details);
            $deletePurchase->payments()->delete();
            $deletePurchase->purchase_details()->delete();
            $deletePurchase->delete();

            DB::commit();
            $message = $deletePurchase->voucher_no . ' is deleted successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendErrorResponse('Something went wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getTotalPurchaseList(Request $request)
    {
        $getPurchases = Purchase::with(['purchase_details', 'supplier']);

        try {
            $search = $request->query('searchBy');
            if ($search) {
                $getPurchases->where(function ($query) use ($search) {
                    $query->where('voucher_no', 'like', "%$search%")
                        ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                            $supplierQuery->where('name', 'like', "%$search%");
                        })
                        ->orWhereHas('purchase_details', function ($detailsQuery) use ($search) {
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
                $getPurchases->whereBetween('purchase_date', [$startDate, $endDate]);
            }

            // Supplier Filtering
            $supplierId = $request->query('supplierId');
            if ($supplierId) {
                $getPurchases->where('supplier_id', $supplierId);
            }

            $total_purchase_amount = $getPurchases->sum('total_amount');
            $total_pay_amount = $getPurchases->sum('pay_amount');
            $total_remain_amount = $getPurchases->sum('remain_amount');

            // Handle order and column
            $order = $request->query('order', 'desc'); // default to asc if not provided
            $column = $request->query('column', 'purchase_date'); // default to id if not provided
            $perPage = $request->query('perPage', 10);

            $result = new stdClass;
            $result->data = $getPurchases->orderBy($column, $order)->paginate($perPage);
            $result->total_purchase_amount = $total_purchase_amount;
            $result->total_pay_amount = $total_pay_amount;
            $result->total_remain_amount = $total_remain_amount;

            $resourceCollection = new PurchasesListResource($result);

            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
        } catch (Exception $e) {
            info($e->getMessage());
            return $this->sendErrorResponse('Error getting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function getPreReturnFormData(Request $request, $id)
    {
        $purchaseData =  Purchase::with('purchase_details')->findOrFail($id);
        
        $resourceCollection = new PrePurchaseReturnResource($purchaseData);

        return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
    }
}
