<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRequest;
use App\Http\Resources\BranchUserResource;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

use App\Traits\BranchTrait;
use Illuminate\Support\Facades\Auth;
use stdClass;

class BranchController extends ApiBaseController
{
    use BranchTrait;

    public function __construct()
    {
        $this->middleware('permission:branch:get')->only('index');
        $this->middleware('permission:branch:create')->only('store');
        $this->middleware('permission:branch:detail')->only('show');
        $this->middleware('permission:branch:edit')->only('update');
        $this->middleware('permission:branch:delete')->only('delete');
    }
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
        $order = $request->query('order', 'desc '); // default to asc if not provided
        $column = $request->query('column', 'id'); // default to id if not provided
    
        $branches->orderBy($column, $order);
    
        // Get the final result
        $branches = $branches->get();

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $branches);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BranchRequest $request)
    {
        try{
            $createBranch = $this->createBranch($request);
            $message = 'Branch ('.$createBranch->name.') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
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

        $data = new stdClass;
        $data->branch = $this->getBranch($branch_id);;
        $data->stocks = $this->getBranchStocks($branch_id);
        // info($branch_id);
        // $getBranch['user_list'] = $this->getBranchUsers($branch_id);
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BranchRequest $request, string $id)
    {
        $branch_id = $id;
        $getBranch = $this->getBranch($branch_id);
        
        $updateBranch = $this->updateBranch($getBranch, $request);
        $message = 'Branch ('.$updateBranch->name.') is updated successfully';

        return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $branch_id = $id;
        $getBranch = $this->getBranch($branch_id);
        if($this->checkBranchHasRelatedData($branch_id)){
            return $this->sendErrorResponse('There are related data with '.$getBranch->name, Response::HTTP_CONFLICT);
        }
        $deleteBranch = $getBranch->delete();
        
        $message = 'Branch ('.$getBranch->name.') is deleted successfully';

        return $this->sendSuccessResponse($message, Response::HTTP_OK);
    }

    public function getUserLists($branch_id, Request $request)
    {
        $perPage = $request->query('perPage', 10); 
        $search = $request->query('searchBy');

        $userQuery = User::where('branch_id', $branch_id);

        if ($search) {
            $userQuery->where('name' , 'Like', "%$search%");
        }

        $results =$userQuery->paginate($perPage);

        $resultCollection = new BranchUserResource($results);
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $resultCollection);
    }
}
