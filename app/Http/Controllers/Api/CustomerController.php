<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequet;
use App\Http\Resources\CustomerListResource;
use App\Models\Customer;
use Exception;
use Illuminate\Http\{
    Request,
    Response
};

use App\Traits\CustomerTrait;
use Illuminate\Support\Facades\DB;

class CustomerController extends ApiBaseController
{
    use CustomerTrait;

    public function __construct()
    {
        $this->middleware('permission:customer:get')->only('index');
        $this->middleware('permission:customer:create')->only('store');
        $this->middleware('permission:customer:detail')->only('show');
        $this->middleware('permission:customer:edit')->only('update');
        $this->middleware('permission:customer:delete')->only('delete');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $getCustomers = Customer::select('*',
                    DB::raw('COALESCE((SELECT SUM(COALESCE(s.remain_amount, 0)) - SUM(COALESCE(p.pay_amount, 0)) FROM sales s
                    LEFT JOIN payments p ON customers.id = p.subject_id AND p.type = "Customer"
                    WHERE customers.id = s.customer_id GROUP BY customers.id), 0) as debt_amount')
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
        //
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
    public function destroy(string $id)
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
}
