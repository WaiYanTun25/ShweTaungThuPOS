<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseReturnRequest;
use App\Http\Resources\PurchaseReturnDetailResource;
use App\Http\Resources\PurchaseReturnListResource;
use App\Models\PurchaseReturn;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\PurchaseReturnTrait;
use App\Traits\TransactionTrait;
use App\Traits\PaymentTrait;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use stdClass;

class PurchaseReturnController extends ApiBaseController
{
    use PurchaseReturnTrait, TransactionTrait, PaymentTrait;
    /**
     * Display a listing of the resource.
     */

    public function __construct()
    {
        $checkPermission = request()->query('permission') === 'True';
        // Conditionally apply permission middleware
        if ($checkPermission) {
            $this->middleware('permission:purchases:read')->only('index', 'show');
            $this->middleware('permission:purchases:create')->only('store');
            $this->middleware('permission:purchases:edit')->only('update');
            $this->middleware('permission:purchases:delete')->only('destroy');
        }
    }
    public function index(Request $request)
    {
        $getPurchaseReturn = PurchaseReturn::with('purchase_return_details');

        try {
            $search = $request->query('searchBy');
            if ($search) {
                $getPurchaseReturn->where(function ($query) use ($search) {
                    $query->where('voucher_no', 'like', "%$search%")
                        ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                            $supplierQuery->where('name', 'like', "%$search%");
                        })
                        ->orWhereHas('purchase_return_details', function ($detailsQuery) use ($search) {
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
               $getPurchaseReturn->whereDate('purchase_return_date', '>=', $startDate)
                ->whereDate('purchase_return_date', '<=', $endDate);
            }

            // Supplier Filtering
            $supplierId = $request->query('supplier_id');
            if ($supplierId) {
                $getPurchaseReturn->where('supplier_id', $supplierId);
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
                $resourceCollection = new PurchaseReturnListResource($result, true);
                return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
            }else{
                // return $getPurchaseReturn->orderBy($column, $order)->get();
                $result->data = $getPurchaseReturn->orderBy($column, $order)->paginate($perPage);
            }

            $resourceCollection = new PurchaseReturnListResource($result);

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

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $getPurchaseReturn = PurchaseReturn::with('purchase_return_details')->findOrFail($id);

        $getCollection = new PurchaseReturnDetailResource($getPurchaseReturn);


        return $this->sendSuccessResponse('Success', Response::HTTP_OK, $getCollection);
    }

    /**
     * Update the specified resource in storage.
     */
    /** need to fix */
    public function update(PurchaseReturnRequest $request, string $id)
    {
        $validatedData = $request->validated();
        DB::beginTransaction();
        try{
            /** handle prev data **/
            // find sale returns by id
            $purchaseReturn = PurchaseReturn::findOrFail($id);
            // update the purchase return
            $updatedPurchaseReturn = $this->createOrUpdatePurchaseReturn($validatedData, Auth::user()->branch_id, true, $purchaseReturn);
            // deduct item from branch
            $this->addItemtoBranch($purchaseReturn->purchase_return_details);
            // delete prev sales return details
            $purchaseReturn->purchase_return_details()->delete(); 

            $updatedPurchaseReturn->purchase_return_details()->createMany($validatedData['purchase_return_details']);
            $this->deductItemFromBranch($validatedData['purchase_return_details'], Auth::user()->branch_id);
            DB::commit();

            $message = 'Purchase Return (' . $updatedPurchaseReturn->voucher_no . ') is updated successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
         }catch(Exception $e){
            DB::rollBack();
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    /** need to fix */
    public function destroy(string $id)
    {
        $deleteSalesReturn = PurchaseReturn::findOrFail($id);
        DB::beginTransaction();
        try {
            $this->addItemtoBranch($deleteSalesReturn->purchase_return_details);
            $deleteSalesReturn->purchase_return_details()->delete();
            $deleteSalesReturn->delete();

            DB::commit();
            $message = 'Purchase return' . $deleteSalesReturn->voucher_no . ' is deleted successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
