<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\PaymentMethodRequest;
use Exception;

class PaymentMethodController extends ApiBaseController
{

    public function __construct()
    {
        // Check if the 'permission' query parameter is present and set to 'true'
        $checkPermission = request()->query('permission') === 'True';
        // Conditionally apply permission middleware
        if ($checkPermission) {
            $this->middleware('permission:payment:read')->only('index', 'show');
            $this->middleware('permission:payment:create')->only('store');
            $this->middleware('permission:payment:edit')->only('update');
            $this->middleware('permission:payment:delete')->only('destroy'); // this api is still remain
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $getPaymentMethod = PaymentMethod::get();
        return $this->sendSuccessResponse('Success', Response::HTTP_OK, $getPaymentMethod);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PaymentMethodRequest $request)
    {
       try{
        $data = $request->validated();

        $createdPayment = PaymentMethod::create([
            'name' => $data['name']
        ]);

        $message = 'Payment Method (' . $createdPayment->name . ') is created successfully';
        return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
       }catch(Exception $e){
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
    public function update(PaymentMethodRequest $request, string $id)
    {
        try {
            $data = $request->validated();
            
            // Find the payment method by ID
            $paymentMethod = PaymentMethod::findOrFail($id);
            
            // Update the payment method with the validated data
            $paymentMethod->update([
                'name' => $data['name'],
            ]);

            $message = 'Payment Method (' . $paymentMethod->name . ') is updated';
            
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $check = PaymentMethod::findOrFail($id);

        if($check) {
            $check->is_active = 0;
            $check->save();

            $message = 'Payment Method (' . $check->name . ') is deleted';
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        }
    }
}
