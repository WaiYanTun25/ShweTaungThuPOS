<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequet;
use App\Http\Requests\PaymentRequest;
use App\Http\Resources\CustomerDetailResource;
use App\Http\Resources\CustomerListResource;
use App\Http\Resources\CustomerRecentOrdersListResource;
use App\Http\Resources\CustomerRecentPaymentListResource;
use App\Http\Resources\CustomerRecentSalesListResource;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SalesOrder;
use Exception;
use Illuminate\Http\{
    Request,
    Response
};

use App\Traits\CustomerTrait;
use DateTime;
use Illuminate\Support\Facades\DB;

class CustomerController extends ApiBaseController
{
    use CustomerTrait;

    public function __construct()
    {
        $checkPermission = request()->query('permission') === 'True';
        // Conditionally apply permission middleware
        if ($checkPermission) {
            $this->middleware('permission:customer:read')->only('index', 'show');
            $this->middleware('permission:customer:create')->only('store');
            $this->middleware('permission:customer:edit')->only('update');
            $this->middleware('permission:customer:delete')->only('delete');
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $getCustomers = Customer::select('*',
                    DB::raw('COALESCE(
                        (SELECT SUM(COALESCE(s.remain_amount, 0)) FROM sales s WHERE s.customer_id = customers.id) -
                        (SELECT COALESCE(SUM(p.pay_amount), 0) FROM payments p WHERE p.subject_id = customers.id AND p.type = "Customer")
                    , 0) as debt_amount')
                    );
        // filters
        $cityID = $request->query('city_id');
        $townshipID = $request->query('township_id');
        $hasDebt = $request->query('hasDebt');
        $hasNoDebt = $request->query('hasNoDebt');

        // report 
        $report = $request->query('report');

        if ($cityID) {
            $getCustomers->where('city', $cityID);
        }
        if ($townshipID) {
            $getCustomers->where('township', $townshipID);
        }

        if($hasDebt && $hasNoDebt || !$hasDebt && !$hasNoDebt){ 
          
        } elseif ($hasDebt && !$hasNoDebt) {
            $getCustomers->having('debt_amount', '>' , 0);
        } else if(!$hasDebt && $hasNoDebt) {
            $getCustomers->having('debt_amount', '=' , 0);
        }

        $search = $request->query('searchBy');
        if ($search)
        {
            $getCustomers->where('name', 'like', "%$search%");
        }
        // Handle order and column
        $order = $request->query('order', 'asc'); // default to asc if not provided
        $column = $request->query('column', 'join_date'); // default to id if not provided
        $perPage = $request->query('perPage', 10);
        $getCustomers->orderBy($column, $order);
        
        if ($report == 'True') {
            $customers = $getCustomers->get();
            $getCollection = new CustomerListResource($customers, true);
            return $this->sendSuccessResponse('success', Response::HTTP_OK, $getCollection);
        }
        
        $customers = $getCustomers->paginate($perPage);

        $getCollection = new CustomerListResource($customers);

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $getCollection);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CustomerRequet $request)
    {
        try{
            $createCustomer = new Customer();
            $createCustomer->name = $request->name;
            $createCustomer->address = $request->address;
            $createCustomer->phone_number = $request->phone_number;
            $createCustomer->township = $request->township;
            $createCustomer->city = $request->city;
            $createCustomer->customer_type = $request->customer_type == "General" ? Customer::GENERAL : Customer::SPECIFIC;
            $createCustomer->save();

            $message = 'Customer ('.$createCustomer->name.') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        }catch(Exception $e){
            info($e->getMessage());
            return $this->sendErrorResponse('Something went wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $getCustomer = Customer::findOrFail($id);
        $getCollection = new CustomerDetailResource($getCustomer);

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $getCollection);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CustomerRequet $request, string $id)
    {
        $updatedCustomer = Customer::findOrFail($id);
        try{
            $updatedCustomer->name = $request->name;
            $updatedCustomer->address = $request->address;
            $updatedCustomer->phone_number = $request->phone_number;
            $updatedCustomer->township = $request->township;
            $updatedCustomer->city = $request->city;
            $updatedCustomer->customer_type = $updatedCustomer->customer_type;
            $updatedCustomer->save();

            $message = 'Customer ('.$updatedCustomer->name.') is updated successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        }catch(Exception $e){
            info($e->getMessage());
            return $this->sendErrorResponse('Something went wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        try{
            if($this->checkCustomerHasRelatedData($customer))
            {
                return $this->sendErrorResponse('There are related data with '.$customer->name, Response::HTTP_CONFLICT);
            }
            $customer->delete();

            $message = 'Customer ('.$customer->name.') is deleted successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        }catch(Exception $e){
            info($e->getMessage());
            return $this->sendErrorResponse('Something went wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getCustomerSales(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        try {
            $customerSales = Sale::where('customer_id', $id);

            $search = $request->query('searchBy');
            if ($search)
            {
                $customerSales->where(function ($query) use ($search) {
                    $query->where('voucher_no', 'like', "%$search%")
                        ->orWhereHas('sales_details', function ($detailsQuery) use ($search) {
                            $detailsQuery->whereHas('item', function ($itemQuery) use ($search) {
                                $itemQuery->where('item_name', 'like', "%$search%");
                            });
                        });
                });
            }
    
            $order = $request->query('order', 'desc'); // default to asc if not provided
            $column = $request->query('column', 'id'); // default to id if not provided
            $perPage = $request->query('perPage', 10);
    
            $customerSales = $customerSales->orderBy($column, $order)->paginate($perPage);
            // return $customerSales;
            $resourceCollection = new CustomerRecentSalesListResource($customerSales);

            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
        }catch(Exception $e){
            return $this->sendErrorResponse('Error getting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function getCustomerOrders(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        try {
            $customerOrders = SalesOrder::where('customer_id', $id);

            $search = $request->query('searchBy');
            if ($search)
            {
                $customerOrders->where(function ($query) use ($search) {
                    $query->where('voucher_no', 'like', "%$search%")
                        ->orWhereHas('sales_order_details', function ($detailsQuery) use ($search) {
                            $detailsQuery->whereHas('item', function ($itemQuery) use ($search) {
                                $itemQuery->where('item_name', 'like', "%$search%");
                            });
                        });
                });
            }
    
            $order = $request->query('order', 'desc'); // default to asc if not provided
            $column = $request->query('column', 'id'); // default to id if not provided
            $perPage = $request->query('perPage', 10);
    
            $customerOrders = $customerOrders->orderBy($column, $order)->paginate($perPage);
            $resourceCollection = new CustomerRecentOrdersListResource($customerOrders);

            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
        }catch(Exception $e){
            return $this->sendErrorResponse('Error getting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function getPaymentHistory(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        try{
            $getPayments = Payment::where([
                'subject_id' => $id,
                'type' => Payment::Customer
            ]);

            $order = $request->query('order', 'desc'); // default to asc if not provided
            $column = $request->query('column', 'payment_date'); // default to id if not provided
            $perPage = $request->query('perPage', 10);

            $getPayments = $getPayments->orderBy($column, $order)->paginate($perPage);
            $resourceCollection = new CustomerRecentPaymentListResource($getPayments);

            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
        }catch(Exception $e){
            return $this->sendErrorResponse('Error getting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function createCustomerPayment(PaymentRequest $request, $type)
    {
        $validatedData = $request->validated();

        try{
            $createPayment = new Payment();
            $createPayment->type = $type == "customers" ? Payment::Customer : Payment::Supplier;
            $createPayment->subject_id = $validatedData['customer_id'] ? $validatedData['customer_id'] : $validatedData['supplier_id'];
            $createPayment->payment_method_id = $validatedData['payment_method_id'];

            $timestamp = DateTime::createFromFormat('j/n/y', $validatedData['payment_date']);
            $mysqlTimestamp = $timestamp->format('Y-m-d H:i:s');

            $createPayment->payment_date = $mysqlTimestamp;
            $createPayment->pay_amount = $validatedData['amount'];
            $createPayment->save();
            return $this->sendSuccessResponse('Success', Response::HTTP_CREATED);
        }catch(Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
