<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\PurchaseRequest;
use App\Http\Resources\PurchaseDetailResource;
use App\Http\Resources\PurchasesListResource;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
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

class PurchaseController extends ApiBaseController
{
    use PurchaseTrait, TransactionTrait, PaymentTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $purchase = Purchase::find(22);

        return $purchase->payments;
        // $activityLog = Activity::inLog('PURCHASE')
        // ->where('subject_id', 25)
        // ->first();
        // $activityLog->properties['attributes']['pay_amount'] = [
        //     "test" => 1000,
        // ];

        // return $activityLog->properties['attributes'];
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
            // Create the purchase
            $createdPurchase = $this->createOrUpdatePurchase($validatedData, Auth::user()->branch_id);
            // Create the purchase details
            // $createdDetails = $this->createPurchaseDetail($validatedData, $createdPurchase->id);
            $createdPurchase->purchase_details()->createMany($validatedData['purchase_details']);
            // Add the items to the branch
            $this->addItemToBranch($validatedData['purchase_details']);

            // Create a payment if the payment status is not 'UN_PAID'
            if ($validatedData['payment_status'] != 'UN_PAID') {
                $this->createPayment($validatedData, $createdPurchase->id);
            }
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
            // update the purchase
            $this->createOrUpdatePurchase($validatedData, Auth::user()->branch_id, true, $updatePurchase);
            // delete prev payments
            $updatePurchase->payments()->delete();
            // create new payments
            if ($validatedData['payment_status'] != 'UN_PAID') {
                $this->createPayment($validatedData, $updatePurchase->id);
            }
            

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
        $deletePurchase = Purchase::findOrFail($id);
        DB::beginTransaction();
        try {
            $this->addItemtoBranch($deletePurchase->purchase_details);
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
            $filter = $request->query('filterBy');
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

            // Handle order and column
            $order = $request->query('order', 'desc'); // default to asc if not provided
            $column = $request->query('column', 'purchase_date'); // default to id if not provided
            $perPage = $request->query('perPage', 10);

            $result = $getPurchases->orderBy($column, $order)->paginate($perPage);
            $resourceCollection = new PurchasesListResource($result);

            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
        } catch (Exception $e) {
            info($e->getMessage());
            return $this->sendErrorResponse('Error getting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
