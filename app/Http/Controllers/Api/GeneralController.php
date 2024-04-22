<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\GeneralRequest;

use App\Models\General;

class GeneralController extends ApiBaseController
{
    public function __construct()
    {
        $checkPermission = request()->query('permission') === 'True';

        if ($checkPermission) {
            $this->middleware('permission:password:read')->only('index', 'detail');
            $this->middleware('permission:password:create')->only('store');
            $this->middleware('permission:password:detail')->only('show');
            $this->middleware('permission:password:edit')->only('update');
            $this->middleware('permission:password:delete')->only('delete');
        }
    }

    /**
     * Display a listing of the resource.
     * General will has always one record
     */
    public function index()
    {
        $general = General::first('password');
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $general);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(GeneralRequest $request)
    {
        $data = $request->validated();

        General::create([
            'password'=> $data['password']
        ]);

        $message = 'Password is created successfully';

        return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $getGeneral = General::first('password');

        return $this->sendSuccessResponse('Success', Response::HTTP_OK, $getGeneral);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(GeneralRequest $request)
    {
        $validatedData = $request->validated();
        $general = General::first();

        if ($general) {
            // Update the record
            $general->update([
                'password' => $validatedData['password']
            ]);
    
            // Return success response
            return $this->sendSuccessResponse('Password updated successfully', Response::HTTP_OK);
        } else {
            // Return error response if the record doesn't exist
            return $this->sendErrorResponse('Record not found', Response::HTTP_NOT_FOUND);
        }

        // return $this->sendSuccessResponse('Success', Response::HTTP_CREATED);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
         // Find the record with ID 1
        $general = General::first();

        // Check if the record exists
        if ($general) {
            // Delete the record
            $general->delete();

            // Return success response
            return $this->sendSuccessResponse('Record deleted successfully', Response::HTTP_OK);
        } else {
            // Return error response if the record doesn't exist
            return $this->sendErrorResponse('Record not found', Response::HTTP_NOT_FOUND);
        }
    }
}
