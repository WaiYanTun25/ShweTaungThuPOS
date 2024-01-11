<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrderRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Traits\PurchaseOrderTrait;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends ApiBaseController
{
    use PurchaseOrderTrait;
    public function create(PurchaseOrderRequest $request)
    {
        $validatedData = $request->validated();

        DB::beginTransaction();
        try{
            $createdPurchaseOrder = $this->createOrUpdatePurchaseOrder($validatedData, Auth::user()->branch_id);

            DB::commit();

            $message = 'Purchase Order (' . $createdPurchaseOrder->voucher_no . ') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        }catch(Exception $e){
            DB::rollBack();
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
