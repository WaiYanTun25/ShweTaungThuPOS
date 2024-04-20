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
    Payment
};
use stdClass;


class TotalPaymentController extends ApiBaseController
{
    public function salesPaymentList(Request $request)
    {
        try {
            // Fetch sales data
            $sales = Sale::query();
        
            // Fetch payments data
            $payments = Payment::where('type', Payment::Customer);
        
            $order = $request->query('order', 'desc');
            $column = $request->query('column', 'pay_date');
            $perPage = $request->query('perPage', 10);
        
            $startDate = $request->query('startDate');
            $endDate = $request->query('endDate');
            $paymentMethodId = $request->query('payment_method_id');
        
            // Apply filters for sales data
            if ($startDate && $endDate) {
                $sales->whereDate('sales_date', '>=', $startDate)
                    ->whereDate('sales_date', '<=', $endDate);
            }
        
            // Apply filters for payments data
            if ($startDate && $endDate) {
                $payments->whereDate('payment_date', '>=', $startDate)
                    ->whereDate('payment_date', '<=', $endDate);
            }
        
            if ($paymentMethodId) {
                $payments->where('payment_method_id', $paymentMethodId);
            }
        
            // Merge the results of sales and payments
            $mergedData = $sales->get()->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'voucher_no' => $sale->voucher_no,
                    'customer_name' => $sale->customer->name,
                    'pay_date' => $sale->sales_date, // Assuming sales_date is equivalent to pay_date
                ];
            })->merge($payments->get()->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'voucher_no' => $payment->voucher_no,
                    'customer_name' => $payment->customer->name, 
                    'pay_date' => $payment->payment_date, // Assuming payment_date is equivalent to pay_date
                ];
            }));

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
}
