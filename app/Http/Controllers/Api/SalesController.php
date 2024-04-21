<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesRequest;
use App\Http\Resources\SalesDetailResource;
use App\Http\Resources\SalesListResource;
use App\Models\Sale;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Traits\{
    SalesTrait,
    TransactionTrait
};
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use stdClass;

class SalesController extends ApiBaseController
{
    use SalesTrait, TransactionTrait;
    /**
     * Display a listing of the resource.
     */

    public function __construct()
    {
        // Check if the 'permission' query parameter is present and set to 'true'
        $checkPermission = request()->query('permission') === 'True';
        // Conditionally apply permission middleware
        if ($checkPermission) {
            $this->middleware('permission:sales:read')->only('index', 'show');
            $this->middleware('permission:sales:create')->only('store');
            $this->middleware('permission:sales:edit')->only('update');
            $this->middleware('permission:sales:delete')->only('destroy'); // this api is still remain
        }
    }


    public function getSalesSummary(Request $request)
    {

        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $today = now()->toDateString();
        $durationType = $request->duration_type ? $request->duration_type : 1;

        try {
            $salesQuery = Sale::query();
            $getReturnQuery = SalesReturn::with('sales_return_details');

            // Apply duration type conditions
            if ($durationType == 1) {
                $salesQuery->whereDate('sales_date', $today);
                $orderQuery = SalesOrder::whereDate('order_date', $today);
                $getReturnQuery->whereDate('sales_return_date', $today);
            } elseif ($durationType == 2) {
                $salesQuery->whereMonth('sales_date', $currentMonth)->whereYear('sales_date', $currentYear);
                $orderQuery = SalesOrder::whereMonth('order_date', $currentMonth)->whereYear('order_date', $currentYear);
                $getReturnQuery->whereMonth('sales_return_date', $currentMonth)->whereYear('sales_return_date', $currentYear);
            } elseif ($durationType == 3) {
                $salesQuery->whereYear('sales_date', $currentYear);
                $orderQuery = SalesOrder::whereYear('order_date', $currentYear);
                $getReturnQuery->whereYear('sales_return_date', $currentYear);
            }

            // sales report
            $getTotalSales = $salesQuery->sum('total_amount');
            $getTotalSalesCount = $salesQuery->count();

            // sales financial report
            $getTotalSalesPay = $salesQuery->sum('pay_amount');
            $getTotalSalesDebt = $salesQuery->sum('remain_amount');

            // sales order report
            $getTotalOrderCount = $orderQuery->count();

            // sales return reports
            $getTotalSalesReturnProduct = $getReturnQuery->get()->sum(function ($return) {
                return $return->sales_return_details->sum('quantity');
            });
            
            $getTotalSalesReturnAmount = $getReturnQuery->sum('pay_amount');

            $result = new stdClass;
            $result->total_sales = [
                'total_sales_amount' => $getTotalSales,
                'total_sales_count' => $getTotalSalesCount,
            ];

            $result->financial_report = [
                'total_pay_amount' => $getTotalSalesPay,
                'total_remain_amount' => $getTotalSalesDebt
            ];

            $result->order = [
                'total_order_count' => $getTotalOrderCount,
            ];

            $result->sales_return = [
                'total_return_product' => $getTotalSalesReturnProduct,
                'total_return_amount' => $getTotalSalesReturnAmount
            ];

            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $result);
        } catch (Exception $e) {
            return $this->sendErrorResponse('Something Went Wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        // $currentMonth = Carbon::now()->month;
        // $currentYear = Carbon::now()->year;
        // $today = now()->toDateString();
        // $durationType = $request->duration_type;

        // try {
        //     // sales report
        //     $getTotalSales = Sale::whereMonth('sales_date', $currentMonth)->whereYear('sales_date', $currentYear)->sum('total_amount');
        //     $getTotalSalesCount = Sale::whereMonth('sales_date', $currentMonth)->whereYear('sales_date', $currentYear)->count();

        //     // sales financial report
        //     $getTotalSalesPay = Sale::whereMonth('sales_date', $currentMonth)->whereYear('sales_date', $currentYear)->sum('pay_amount');
        //     $getTotalSalesDebt = Sale::whereMonth('sales_date', $currentMonth)->whereYear('sales_date', $currentYear)->sum('remain_amount');

        //     // sales order report
        //     $getTotalOrderCount = SalesOrder::whereMonth('order_date', $currentMonth)->whereYear('order_date', $currentYear)->count();

        //     // sales return reports
        //     $getTotalSalesReturnProduct = SalesReturn::with('sales_return_details')
        //         ->whereMonth('sales_return_date', $currentMonth)
        //         ->whereYear('sales_return_date', $currentYear)
        //         ->withSum('sales_return_details', 'quantity')
        //         ->get()
        //         ->sum('sales_return_details_sum_quantity');

        //     $getTotalSalesReturnAmount = SalesReturn::with('sales_return_details')
        //         ->whereMonth('sales_return_date', $currentMonth)
        //         ->whereYear('sales_return_date', $currentYear)
        //         ->sum('pay_amount');

        //     $result = new stdClass;
        //     $result->total_sales = [
        //         'total_sales_amount' => $getTotalSales,
        //         'total_sales_count' => $getTotalSalesCount,
        //     ];

        //     $result->financial_report = [
        //         'total_pay_amount' => $getTotalSalesPay,
        //         'total_remain_amount' => $getTotalSalesDebt
        //     ];

        //     $result->order = [
        //         'total_order_count' => $getTotalOrderCount,
        //     ];

        //     $result->sales_return = [
        //         'total_return_product' => $getTotalSalesReturnProduct,
        //         'total_return_amount' => $getTotalSalesReturnAmount
        //     ];

        //     return $this->sendSuccessResponse('Success', Response::HTTP_OK, $result);
        // } catch (Exception $e) {
        //     return $this->sendErrorResponse('Something Went Wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        // }
    }

    public function index(Request $request)
    {
        // အဝယ်မှတ်တမ်း
        $getSales = Sale::with(['sales_details', 'customer']);

        try {
            $search = $request->query('searchBy');
            if ($search) {
                $getSales->where(function ($query) use ($search) {
                    $query->where('voucher_no', 'like', "%$search%")
                        ->orWhereHas('customer', function ($supplierQuery) use ($search) {
                            $supplierQuery->where('name', 'like', "%$search%");
                        })
                        ->orWhereHas('sales_details', function ($detailsQuery) use ($search) {
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
                // $getSales->whereBetween('purchase_date', [$startDate, $endDate]);
                $getSales->whereDate('sales_date', '>=', $startDate)
                    ->whereDate('sales_date', '<=', $endDate);
            }

            // Supplier Filtering
            $customer_id = $request->query('customer_id');
            if ($customer_id) {
                $getSales->where('customer_id', $customer_id);
            }
            // payment Filtering
            $payment = $request->query('payment');
            if ($payment) {
                $getSales->where('payment_status', $payment);
            }

            // Handle order and column
            $order = $request->query('order', 'desc'); // default to asc if not provided
            $column = $request->query('column', 'purchase_date'); // default to id if not provided
            $perPage = $request->query('perPage', 10);

            $result = new stdClass;
            if ($request->query('report') == "True") {
                $result->data = $getSales->orderBy($column, $order)->get();
                // this true is always true cause
                // i use this resource function in two place 
                // this controller funciton is always true
                $resourceCollection = new SalesListResource($result, true, true);
                return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
            } else {
                $result->data = $getSales->orderBy($column, $order)->paginate($perPage);
            }

            $resourceCollection = new SalesListResource($result, true);

            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
        } catch (Exception $e) {
            info($e->getMessage());
            return $this->sendErrorResponse('Error getting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SalesRequest $request)
    {
        // Validate the purchase request data
        $validatedData = $request->validated();
        $branch_id = Auth::user()->branch_id;
        DB::beginTransaction();
        try {
            $createdSales = $this->createOrUpdateSales($validatedData, $branch_id);

            $createdSales->sales_details()->createMany($validatedData['sales_details']);

            // Add the items to the branch
            $this->deductItemFromBranch($validatedData['sales_details'], $branch_id, true);

            // Commit the database transaction
            DB::commit();

            $message = 'Sale (' . $createdSales->voucher_no . ') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $getSales = Sale::with('sales_details')->findOrFail($id);
        $result = new SalesDetailResource($getSales);

        return $this->sendSuccessResponse('Success', Response::HTTP_OK, $result);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SalesRequest $request, string $id)
    {
        $updatedSales = Sale::findOrFail($id);
        DB::beginTransaction();

        try {
            $validatedData = $request->validated();
            $this->createOrUpdateSales($validatedData, Auth::user()->branch_id, true, $updatedSales);

            $this->addItemToBranch($validatedData['sales_details']);

            // deduct prev quantity from branch
            $this->deductItemFromBranch($updatedSales->sales_details, Auth::user()->branch_id, true);

            DB::commit();
            $message = 'Sales (' . $updatedSales->voucher_no . ') is updated successfully';
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
        //
    }

    // addon
    public function getTotalSalesList(Request $request)
    {
        $getSales = Sale::with(['sales_details', 'customer']);

        try {
            $search = $request->query('searchBy');
            if ($search) {
                $getSales->where(function ($query) use ($search) {
                    $query->where('voucher_no', 'like', "%$search%")
                        ->orWhereHas('customer', function ($supplierQuery) use ($search) {
                            $supplierQuery->where('name', 'like', "%$search%");
                        })
                        ->orWhereHas('sales_details', function ($detailsQuery) use ($search) {
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
                // $getSales->whereBetween('purchase_date', [$startDate, $endDate]);
                $getSales->whereDate('sales_date', '>=', $startDate)
                    ->whereDate('sales_date', '<=', $endDate);
            }

            // Supplier Filtering
            $customer_id = $request->query('customer_id');
            if ($customer_id) {
                $getSales->where('customer_id', $customer_id);
            }

            $total_sales_amount = $getSales->sum('total_amount');
            $total_pay_amount = $getSales->sum('pay_amount');
            $total_remain_amount = $getSales->sum('remain_amount');

            // Handle order and column
            $order = $request->query('order', 'desc'); // default to asc if not provided
            $column = $request->query('column', 'sales_date'); // default to id if not provided
            $perPage = $request->query('perPage', 10);

            $result = new stdClass;

            $result->total_sales_amount = $total_sales_amount;
            $result->total_pay_amount = $total_pay_amount;
            $result->total_remain_amount = $total_remain_amount;
            if ($request->query('report') == "True") {
                $result->data = $getSales->orderBy($column, $order)->get();
                // this true is always true cause
                // i use this resource function in two place 
                // this controller funciton is always true
                $resourceCollection = new SalesListResource($result, false, true);
                return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
            } else {
                $result->data = $getSales->orderBy($column, $order)->paginate($perPage);
            }

            $resourceCollection = new SalesListResource($result);

            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
        } catch (Exception $e) {
            info($e->getMessage());
            return $this->sendErrorResponse('Error getting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
