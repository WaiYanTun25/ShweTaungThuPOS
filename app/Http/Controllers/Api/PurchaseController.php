<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\PurchaseRequest;
use App\Http\Resources\PrePurchaseReturnResource;
use App\Http\Resources\PurchaseDetailResource;
use App\Http\Resources\PurchasesListResource;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
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
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use stdClass;

class PurchaseController extends ApiBaseController
{
    use PurchaseTrait, TransactionTrait, PaymentTrait;
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

    public function getPurchaseSummary(Request $request)
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $today = now()->toDateString();
        $durationType = $request->duration_type;

    try {
        $purchaseQuery = Purchase::query();
        $getReturnQuery = PurchaseReturn::with('purchase_return_details');

        // Apply duration type conditions
        if ($durationType == 1) {
            $purchaseQuery->whereDate('purchase_date', $today);
            $supplierQuery = Supplier::whereDate('join_date', $today);
            $orderQuery = PurchaseOrder::whereDate('order_date', $today);
            $getReturnQuery->whereDate('purchase_return_date', $today);
        } elseif ($durationType == 2) {
            $purchaseQuery->whereMonth('purchase_date', $currentMonth)->whereYear('purchase_date', $currentYear);
            $supplierQuery = Supplier::whereMonth('join_date', $currentMonth)->whereYear('join_date', $currentYear);
            $orderQuery = PurchaseOrder::whereMonth('order_date', $currentMonth)->whereYear('order_date', $currentYear);
            $getReturnQuery->whereMonth('purchase_return_date', $currentMonth)->whereYear('purchase_return_date', $currentYear);
        } elseif ($durationType == 3) {
            $purchaseQuery->whereYear('purchase_date', $currentYear);
            $supplierQuery = Supplier::whereYear('join_date', $currentYear);
            $orderQuery = PurchaseOrder::whereYear('order_date', $currentYear);
            $getReturnQuery->whereYear('purchase_return_date', $currentYear);
        }

        // sales report
        $getTotalPurchases = $purchaseQuery->sum('total_amount');
        $getTotalPurchasesCount = $purchaseQuery->count();

        // sales financial report
        $getSupplierCount = $supplierQuery->count();
        $getTotalSupplierCount = Supplier::count();

        // sales order report
        $getTotalOrderCount = $orderQuery->count();

        // sales return reports
        $getTotalPurchasesReturnProduct = $getReturnQuery->get()->sum(function ($return) {
            return $return->purchase_return_details->sum('quantity');
        });
        
        $getTotalPurchasesReturnAmount = $getReturnQuery->sum('pay_amount');

        $result = new stdClass;
        $result->total_sales = [
            'total_purchase_amount' => $getTotalPurchases,
            'total_purchase_count' => $getTotalPurchasesCount,
        ];

        $result->suppliers = [
            "total_suppliers" => $getTotalSupplierCount,
            "this_month_suppliers" => $getSupplierCount
        ];

        $result->order = [
            'total_order_count' => $getTotalOrderCount,
        ];

        $result->sales_return = [
            'total_return_product' => $getTotalPurchasesReturnProduct,
            'total_return_amount' => $getTotalPurchasesReturnAmount
        ];

        return $this->sendSuccessResponse('Success', Response::HTTP_OK, $result);
    } catch (Exception $e) {
        return $this->sendErrorResponse('Something Went Wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
        // try {
        //     // sales report
        //     $getTotalPurchases = Purchase::whereMonth('purchase_date', $currentMonth)->whereYear('purchase_date', $currentYear)->sum('total_amount');
        //     $getTotalPurchasesCount = Purchase::whereMonth('purchase_date', $currentMonth)->whereYear('purchase_date', $currentYear)->count();

        //     // // sales financial report
        //     // $getTotalPurchasesPay = Purchase::whereMonth('purchase_date', $currentMonth)->whereYear('purchase_date', $currentYear)->sum('pay_amount');
        //     // $getTotalPurchasesDebt = Purchase::whereMonth('purchase_date', $currentMonth)->whereYear('purchase_date', $currentYear)->sum('remain_amount');
        //     $getSupplierCount = Supplier::whereMonth('join_date', $currentMonth)->whereYear('join_date', $currentYear)->count();
        //     $getTotalSupplierCount = Supplier::count();

        //     // sales order report
        //     $getTotalOrderCount = PurchaseOrder::whereDate('order_date', $today)->count();

        //     // sales return reports
        //     $getTotalPurchasesReturnProduct = PurchaseReturn::with('purchase_return_details')
        //         ->whereDate('purchase_return_date', $today)
        //         ->withSum('purchase_return_details', 'quantity')
        //         ->get()
        //         ->sum('purchase_return_details_sum_quantity');
        //     $getTotalPurchasesReturnAmount = PurchaseReturn::with('purchase_return_details')
        //         ->whereDate('purchase_return_date', $today)
        //         ->sum('pay_amount');

        //     $result = new stdClass;
        //     $result->total_sales = [
        //         'total_purchase_amount' => $getTotalPurchases,
        //         'total_purchase_count' => $getTotalPurchasesCount,
        //     ];

        //     $result->suppliers = [
        //         "total_suppliers" => $getTotalSupplierCount,
        //         "this_month_suppliers" => $getSupplierCount
        //     ];

        //     $result->order = [
        //         'total_order_count' => $getTotalOrderCount,
        //     ];

        //     $result->sales_return = [
        //         'total_return_product' => $getTotalPurchasesReturnProduct,
        //         'total_return_amount' => $getTotalPurchasesReturnAmount
        //     ];

        //     return $this->sendSuccessResponse('Success', Response::HTTP_OK, $result);
        // } catch (Exception $e) {
        //     return $this->sendErrorResponse('Something Went Wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        // }
    }
    
    public function index(Request $request)
    {
        // အဝယ်မှတ်တမ်း
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
                // $getPurchases->whereBetween('purchase_date', [$startDate, $endDate]);
                $getPurchases->whereDate('purchase_date', '>=', $startDate)
                ->whereDate('purchase_date', '<=', $endDate);
            }

            // Supplier Filtering
            $supplierId = $request->query('supplier_id');
            if ($supplierId) {
                $getPurchases->where('supplier_id', $supplierId);
            }
            // payment Filtering
            $payment = $request->query('payment');
            if ($payment) {
                $getPurchases->where('payment_status', $payment);
            }

            // Handle order and column
            $order = $request->query('order', 'desc'); // default to asc if not provided
            $column = $request->query('column', 'purchase_date'); // default to id if not provided
            $perPage = $request->query('perPage', 10);

            $result = new stdClass;
            if($request->query('report') == "True"){
                $result->data = $getPurchases->orderBy($column, $order)->get();
                // this true is always true cause
                // i use this resource function in two place 
                // this controller function is always true
                $resourceCollection = new PurchasesListResource($result, true, true);
                return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
            }else{
                $result->data = $getPurchases->orderBy($column, $order)->paginate($perPage);
            }

            $resourceCollection = new PurchasesListResource($result, true);

            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
        } catch (Exception $e) {
            info($e->getMessage());
            return $this->sendErrorResponse('Error getting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }

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
        // return $request->amount_type;
        // Validate the purchase request data
        $validatedData = $request->validated();
        // Begin a database transaction
        DB::beginTransaction();
        try {
            // Create a payment if the payment status is not 'UN_PAID'
            // $created_payment = null;
            // if ($validatedData['payment_status'] != 'UN_PAID') {
            //     $created_payment = $this->createPayment($validatedData);
            // }
            // Create the purchase
            $createdPurchase = $this->createOrUpdatePurchase($validatedData, Auth::user()->branch_id);

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
    public function show(Request $request , string $id)
    {
        $isInvoice = $request->query('invoice_preview');
        $getPurchase = Purchase::with('purchase_details')->findOrFail($id);
        $result = $isInvoice == "True" ?  new PurchaseDetailResource($getPurchase, "True") : new PurchaseDetailResource($getPurchase);

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
            // $updatePurchase->payment()->delete();
            // create new payments
            // $created_payment = null;
            // if ($validatedData['payment_status'] != 'UN_PAID') {
            //     $created_payment = $this->createPayment($validatedData, $updatePurchase->id);
            // }
            // update the purchase
            $this->createOrUpdatePurchase($validatedData, Auth::user()->branch_id, true, $updatePurchase);
            
            $this->addItemToBranch($validatedData['purchase_details']);
            // deduct prev quantity from branch
            $this->deductItemFromBranch($updatePurchase->purchase_details, Auth::user()->branch_id);
            $updatePurchase->purchase_details()->delete();

            // create many purchase details
            $updatePurchase->purchase_details()->createMany($validatedData['purchase_details']);
            // Add the items to the branch
            

            DB::commit();
            $message = 'Puchases (' . $updatePurchase->voucher_no . ') is updated successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        } catch (Exception $e) {
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
        // $checkPurchaseReturn = PurchaseReturn::where('purchase_id', $id)->first();

        // if ($checkPurchaseReturn) {
        //     $message = 'This Purchase has Purchase Return data!';
        //     return $this->sendErrorResponse($message, Response::HTTP_BAD_REQUEST);
        // }
        
        $deletePurchase = Purchase::findOrFail($id);
        DB::beginTransaction();
        try {
            $this->deductItemFromBranch($deletePurchase->purchase_details, Auth::user()->branch_id);
            $deletePurchase->purchase_details()->delete();
            $deletePurchase->delete();

            DB::commit();
            $message = $deletePurchase->voucher_no . ' is deleted successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
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
                // $getPurchases->whereBetween('purchase_date', [$startDate, $endDate]);
                $getPurchases->whereDate('purchase_date', '>=', $startDate)
                ->whereDate('purchase_date', '<=', $endDate);
            }

            // Supplier Filtering
            $supplierId = $request->query('supplier_id');
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

            $result->total_purchase_amount = $total_purchase_amount;
            $result->total_pay_amount = $total_pay_amount;
            $result->total_remain_amount = $total_remain_amount;
            if($request->query('report') == "True"){
                $result->data = $getPurchases->orderBy($column, $order)->get();
                // this true is always true cause
                // i use this resource function in two place 
                // this controller funciton is always true
                $resourceCollection = new PurchasesListResource($result, false, true);
                return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
            }else{
                $result->data = $getPurchases->orderBy($column, $order)->paginate($perPage);
            }

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
