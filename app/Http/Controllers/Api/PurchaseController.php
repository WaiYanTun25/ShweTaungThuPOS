<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\PurchaseRequest;
use Exception;
use Illuminate\Http\{
    Request,
    Response
};
use Illuminate\Support\Facades\DB;
use App\Traits\{
    PurchaseTrait,
    TransactionTrait,
    PaymentTrait
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
        //
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
        $createdPurchase = $this->createPurchase($validatedData, Auth::user()->branch_id);
        
        // Create the purchase details
        $this->createPurchaseDetail($validatedData, $createdPurchase->id);
        
        // Add the items to the branch
        $this->addItemToBranch($validatedData['purchase_details']);
        
        // Create a payment if the payment status is not 'UN_PAID'
        if ($validatedData['payment_status'] != 'UN_PAID') {
            $this->createPayment($validatedData, $createdPurchase->id);
        }

        // Commit the database transaction
        DB::commit();
        
        // Return a success response
        $message = 'Purchase ('. $createdPurchase->voucher_no .') is created successfully';
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
        //
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
