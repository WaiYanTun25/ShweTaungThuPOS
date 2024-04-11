<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\GeneralRequest;

use App\Models\General;

class GeneralController extends ApiBaseController
{
    // public function __construct()
    // {
    //     $this->middleware('permission:password:read')->only('index', 'detail');
    //     $this->middleware('permission:password:create')->only('store');
    //     $this->middleware('permission:password:detail')->only('show');
    //     $this->middleware('permission:password:edit')->only('update');
    //     $this->middleware('permission:password:delete')->only('delete');
    // }

    /**
     * Display a listing of the resource.
     * General will has always one record
     */
    public function index()
    {
        $general = General::first();
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
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
