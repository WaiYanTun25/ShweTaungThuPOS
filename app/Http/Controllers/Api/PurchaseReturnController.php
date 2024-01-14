<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseReturnRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\PurchaseReturnTrait;
use App\Traits\TransactionTrait;
use App\Traits\PaymentTrait;
use Illuminate\Support\Facades\Auth;

class PurchaseReturnController extends Controller
{
    use PurchaseReturnTrait, TransactionTrait, PaymentTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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

            $createdPurchaseReturn->purchase_return_details()->createMany($validatedData['purchase_details']);
            $this->deductItemFromBranch($validatedData['purchase_return_details'], Auth::user()->branch_id);
            $this->createPayment($validatedData, $createdPurchaseReturn->id);
            DB::commit();

        }catch(Exception $e){
            DB::rollBack();

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
