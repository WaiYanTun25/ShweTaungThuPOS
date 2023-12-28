<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends ApiBaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return "work";
        $getUsers = User::select('*');
        $search = $request->query('searchBy');

        if ($search) {
            $getUsers->where('name', 'like', "%$search%")
                ->orWhere('phone_number', 'like', "%$search%");
        }

        // Handle order and column
        $order = $request->query('order', 'asc'); // default to asc if not provided
        $column = $request->query('column', 'id'); // default to id if not provided

        $getUsers->orderBy($column, $order);

        // // Add pagination
        $perPage = $request->query('perPage', 10); // default to 10 if not provided
        $users = $getUsers->paginate($perPage);

        return $users;
        // $resourceCollection = new TransferResourceCollection($transfers);

        // return $resourceCollection;
        // return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
