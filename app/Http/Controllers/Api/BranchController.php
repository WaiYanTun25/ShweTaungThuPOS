<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

use App\Traits\BranchTrait;

class BranchController extends ApiBaseController
{
    use BranchTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $branches = Branch::select(['id', 'name', 'phone_number', 'total_employee', 'address']);

        $search = $request->query('search');
        if ($search) {
            $branches->where('name', 'like', "%$search%")
                     ->orWhere('phone_number', 'like', "%$search%")
                     ->orWhere('address', 'like', "%$search%");
        }
    
        // Handle order and column
        $order = $request->query('order', 'asc'); // default to asc if not provided
        $column = $request->query('column', 'created_at'); // default to created_at if not provided
    
        $branches->orderBy($column, $order);
    
        // Get the final result
        $branches = $branches->latest('created_at')->get();

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $branches);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => 'required',
            "phone_number" => "required",
            "address" => 'required'
        ]); 

        if($validator->fails())
        {
            return $this->sendErrorResponse('error', Response::HTTP_UNPROCESSABLE_ENTITY,$validator->errors());
        }

        try{
            $createBranch = $this->createBranch($request);

            return $this->sendSuccessResponse('success', Response::HTTP_CREATED, $createBranch);
        }catch(\Exception $e){
            info($e->getMessage());
            return $this->sendErrorResponse('Something went wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $branch_id = $id;

        $getBranch = $this->getBranch($branch_id);
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $getBranch);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            "name" => 'required',
            "phone_number" => "required",
            "address" => 'required'
        ]); 

        if($validator->fails())
        {
            return $this->sendErrorResponse('error', Response::HTTP_UNPROCESSABLE_ENTITY, $validator->errors());
        }

        $branch_id = $id;
        $getBranch = $this->getBranch($branch_id);
        
        $updateBranch = $this->updateBranch($getBranch, $request);

        return $this->sendSuccessResponse('success', Response::HTTP_CREATED, $updateBranch);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $branch_id = $id;
        $getBranch = $this->getBranch($branch_id);
        $deleteBranch = $getBranch->delete();
        
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $getBranch);
    }
}
