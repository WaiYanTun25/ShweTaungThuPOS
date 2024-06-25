<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\URL;

use Illuminate\Support\Collection;

/** Model List */
use App\Models\{
    Sale,
    Purchase,
    Payment
};
use stdClass;


class TotalPaymentController extends ApiBaseController
{
    public function salesPaymentList(Request $request)
    {
        try {
            // Fetch sales data
            $sales = Sale::where('payment_status', '!=', 'UN_PAID');
        
            // Fetch payments data
            $payments = Payment::where('type', Payment::Customer);
        
            $order = $request->query('order', 'desc');
            $column = $request->query('column', 'pay_date');
            $perPage = $request->query('perPage', 10);
        
            $startDate = $request->query('startDate');
            $endDate = $request->query('endDate');
            $paymentMethodId = $request->query('payment_method_id');
            $customerId = $request->query('customer_id');
        
            // Apply filters for sales data
            if ($startDate && $endDate) {
                $sales->whereDate('sales_date', '>=', $startDate)
                    ->whereDate('sales_date', '<=', $endDate);
                $payments->whereDate('payment_date', '>=', $startDate)
                    ->whereDate('payment_date', '<=', $endDate);
            }

            if ($customerId) {
                $sales->where('customer_id', $customerId);
                $payments->where('subject_id', $customerId);
            }
        
            // Apply filters for payments data
            // if ($startDate && $endDate) {
            //     $payments->whereDate('payment_date', '>=', $startDate)
            //         ->whereDate('payment_date', '<=', $endDate);
            // }
        
            if ($paymentMethodId) {
                $sales->where('payment_method_id', $paymentMethodId);
                $payments->where('payment_method_id', $paymentMethodId);
            }
        
            // Get mapped collections for sales and payments
            $salesData = $sales->get()->map(function ($sale) {
                return [
                    'voucher_no' => $sale->voucher_no,
                    'pay_amount' => $sale->pay_amount,
                    'customer_name' => $sale->customer->name,
                    'pay_date' => $sale->sales_date,
                ];
            });

            $paymentsData = $payments->get()->map(function ($payment) {
                return [
                    'voucher_no' => $payment->voucher_no,
                    'pay_amount' => $payment->pay_amount,
                    'customer_name' => $payment->customer->name,
                    'pay_date' => $payment->payment_date,
                ];
            });

            // If either collection is empty, return the non-empty one or an empty collection
            if ($salesData->isEmpty() && $paymentsData->isEmpty()) {
                // If both are empty, handle appropriately (e.g., return an empty response)
                $mergedData = collect(); // Empty collection
            } else if($salesData->isEmpty()) {
                $mergedData = $paymentsData;
            } else if($paymentsData->isEmpty()) {
                $mergedData = $salesData;
            } else {
                // Merge the collections
                $mergedData = $salesData->merge($paymentsData);
            }

            // Sort the merged data
            $sortedData = $mergedData->sortBy($column, SORT_REGULAR, $order === 'desc');

            // Paginate the sorted data using LengthAwarePaginator
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $currentPageItems = $sortedData->slice(($currentPage - 1) * $perPage, $perPage)->values(); // Reset keys
            $paginatedData = new LengthAwarePaginator($currentPageItems, count($sortedData), $perPage);

            // Customize pagination response
            $paginationResponse = [
                'current_page' => $paginatedData->currentPage(),
                'from' => $paginatedData->firstItem(),
                'last_page' => $paginatedData->lastPage(),
                "links" => new stdClass,
                'path' => URL::current(),
                'to' => $paginatedData->lastItem(),
                'total' => $paginatedData->total(),
            ];

            $paginationLinks = [
                'first' => URL::current().'?page=1',
                'last' => URL::current().'?page='.$paginatedData->lastPage(),
                'prev' => $paginatedData->previousPageUrl() ? URL::current().$paginatedData->previousPageUrl() : null,
                'next' => $paginatedData->hasMorePages() ? URL::current().$paginatedData->nextPageUrl() : null,
            ];
            $result = new stdClass;

            // Retrieve paginated data items
            $data = $paginatedData->items();

            // Add data to the result object
            $result->sale_payment_list = $data;

            $result->links = $paginationLinks;

            // Add pagination metadata to the result object
            $result->meta = $paginationResponse;

            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $result);
            // Return only the paginationResponse array without nesting it under a "pagination" key
        } catch (Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function purchasePaymentList(Request $request)
    {
        try {
            // Fetch purchases data
            $purchases = Purchase::where('payment_status', '!=', 'UN_PAID');

            // Fetch payments data
            $payments = Payment::where('type', Payment::Supplier);
        
            $order = $request->query('order', 'desc');
            $column = $request->query('column', 'pay_date');
            $perPage = $request->query('perPage', 10);
        
            $startDate = $request->query('startDate');
            $endDate = $request->query('endDate');
            $paymentMethodId = $request->query('payment_method_id');
            $supplierId = $request->query('supplier_id');
        
            // Apply filters for sales data
            if ($startDate && $endDate) {
                $purchases->whereDate('purchase_date', '>=', $startDate)
                    ->whereDate('purchase_date', '<=', $endDate);
            }

            if ($supplierId) {
                $purchases->where('supplier_id', $supplierId);
                $payments->where('subject_id', $supplierId);
            }
        
            // Apply filters for payments data
            if ($startDate && $endDate) {
                $payments->whereDate('payment_date', '>=', $startDate)
                    ->whereDate('payment_date', '<=', $endDate);
            }
        
            if ($paymentMethodId) {
                $purchases->where('payment_method_id', $paymentMethodId);
                $payments->where('payment_method_id', $paymentMethodId);
            }
        
            // Merge the results of sales and payments
            $purchaseData = $purchases->get()->map(function ($purchase) {
                return [
                    // 'id' => $sale->id,
                    'voucher_no' => $purchase->voucher_no,
                    'pay_amount' => $purchase->pay_amount,
                    'supplier_name' => $purchase->supplier->name,
                    'pay_date' => $purchase->purchase_date, // Assuming sales_date is equivalent to pay_date
                ];
            });

            $paymentsData = $payments->get()->map(function ($payment) {
                return [
                    // 'id' => $payment->id,
                    'voucher_no' => $payment->voucher_no,
                    'pay_amount' => $payment->pay_amount,
                    'supplier_name' => $payment->supplier->name, 
                    'pay_date' => $payment->payment_date, // Assuming payment_date is equivalent to pay_date
                ];
            });

            // If either collection is empty, return the non-empty one or an empty collection
            if ($purchaseData->isEmpty() && $paymentsData->isEmpty()) {
                // If both are empty, handle appropriately (e.g., return an empty response)
                $mergedData = collect(); // Empty collection
            } else if($purchaseData->isEmpty()) {
                $mergedData = $paymentsData;
            } else if($paymentsData->isEmpty()) {
                $mergedData = $purchaseData;
            } else {
                // Merge the collections
                $mergedData = $purchaseData->merge($paymentsData);
            }

            // Sort the merged data
            $sortedData = $mergedData->sortBy($column, SORT_REGULAR, $order === 'desc');

            // Paginate the sorted data using LengthAwarePaginator
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $currentPageItems = $sortedData->slice(($currentPage - 1) * $perPage, $perPage)->values(); // Reset keys
            $paginatedData = new LengthAwarePaginator($currentPageItems, count($sortedData), $perPage);

            // Customize pagination response
            $paginationResponse = [
                'current_page' => $paginatedData->currentPage(),
                'from' => $paginatedData->firstItem(),
                'last_page' => $paginatedData->lastPage(),
                "links" => new stdClass,
                'path' => URL::current(),
                'to' => $paginatedData->lastItem(),
                'total' => $paginatedData->total(),
            ];

            $paginationLinks = [
                'first' => URL::current().'?page=1',
                'last' => URL::current().'?page='.$paginatedData->lastPage(),
                'prev' => $paginatedData->previousPageUrl() ? URL::current().$paginatedData->previousPageUrl() : null,
                'next' => $paginatedData->hasMorePages() ? URL::current().$paginatedData->nextPageUrl() : null,
            ];
            $result = new stdClass;

            // Retrieve paginated data items
            $data = $paginatedData->items();

            // Add data to the result object
            $result->purchase_payment_list = $data;

            $result->links = $paginationLinks;

            // Add pagination metadata to the result object
            $result->meta = $paginationResponse;

            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $result);
            // Return only the paginationResponse array without nesting it under a "pagination" key
        } catch (Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
