<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesReturnRequest;
use App\Http\Resources\SalesReturnDetailResource;
use App\Http\Resources\SalesReturnListResource;
use App\Models\SalesReturn;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use stdClass;

use App\Traits\{
    SalesReturnTrait,
    TransactionTrait
};

class SalesReturnController extends ApiBaseController
{
    use SalesReturnTrait , TransactionTrait;

    public function __construct()
    {
        $checkPermission = request()->query('permission') === 'True';
        $this->middleware('check.branch')->only('store', 'update');
        // Conditionally apply permission middleware
        if ($checkPermission) {
            $this->middleware('permission:sales:read')->only('index', 'show');
            $this->middleware('permission:sales:create')->only('store');
            $this->middleware('permission:sales:edit')->only('update');
            $this->middleware('permission:sales:delete')->only('destroy');
        }
    }
    public function index(Request $request)
    {
        $getPurchaseReturn = SalesReturn::with('sales_return_details');

        try {
            $search = $request->query('searchBy');
            if ($search) {
                $getPurchaseReturn->where(function ($query) use ($search) {
                    $query->where('voucher_no', 'like', "%$search%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%$search%");
                        })
                        ->orWhereHas('sales_return_details', function ($detailsQuery) use ($search) {
                            $detailsQuery->whereHas('item', function ($itemQuery) use ($search) {
                                $itemQuery->where('item_name', 'like', "%$search%");
                            });
                        });
                });
            }

            // Date Filtering
            $startDate = $request->query('startDate');
            $endDate = $request->query('endDate');

            if ($startDate && $endDate) {
                // $getPurchaseReturn->whereBetween('purchase_date', [$startDate, $endDate]);
               $getPurchaseReturn->whereDate('sales_return_date', '>=', $startDate)
                ->whereDate('sales_return_date', '<=', $endDate);
            }

            // customer Filtering
            $customer_id = $request->query('customer_id');
            if ($customer_id) {
                $getPurchaseReturn->where('customer_id', $customer_id);
            }

           
            // Handle order and column
            $order = $request->query('order', 'desc'); // default to asc if not provided
            $column = $request->query('column', 'purchase_date'); // default to id if not provided
            $perPage = $request->query('perPage', 10);

            $result = new stdClass;
            if($request->query('report') == "True"){
                $result->data = $getPurchaseReturn->orderBy($column, $order)->get();
                // this true is always true cause
                // use this resource function in two place 
                // this controller funciton is always true
                $resourceCollection = new SalesReturnListResource($result, true);
                return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
            }else{
                $result->data = $getPurchaseReturn->orderBy($column, $order)->paginate($perPage);
            }

            $resourceCollection = new SalesReturnListResource($result);

            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
        } catch (Exception $e) {
            info($e->getMessage());
            return $this->sendErrorResponse('Error getting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }

    }

    /**
     * Store a newly created resource in storage.
     */
    public function create(SalesReturnRequest $request)
    {
        $validatedData = $request->validated();
        DB::beginTransaction();
        try{
            // Create the purchase return
            $createdSalesReturn = $this->createOrUpdateSalesReturn($validatedData, Auth::user()->branch_id);

            $createdSalesReturn->sales_return_details()->createMany($validatedData['sales_return_details']);
            $this->addItemtoBranch($validatedData['sales_return_details']);
            DB::commit();

            $message = 'Sales Return (' . $createdSalesReturn->voucher_no . ') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
         }catch(Exception $e){
            DB::rollBack();
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        $getSalesReturn = SalesReturn::with('sales_return_details')->findOrFail($id);

        $getCollection = new SalesReturnDetailResource($getSalesReturn);


        return $this->sendSuccessResponse('Success', Response::HTTP_OK, $getCollection);
    }

    public function update(SalesReturnRequest $request, $id)
    {
        $validatedData = $request->validated();
        DB::beginTransaction();
        try{
            /** handle prev data **/
            // find sale returns by id
            $salesReturn = SalesReturn::findOrFail($id);
            // update the purchase return
            $updatedSalesReturn = $this->createOrUpdateSalesReturn($validatedData, Auth::user()->branch_id, true, $salesReturn);
            // deduct item from branch
            $this->deductItemFromBranch($salesReturn->sales_return_details, Auth::user()->branch_id);
            // delete prev sales return details
            $salesReturn->sales_return_details()->delete(); 

            $updatedSalesReturn->sales_return_details()->createMany($validatedData['sales_return_details']);
            $this->addItemtoBranch($validatedData['sales_return_details']);
            DB::commit();

            $message = 'Sales Return (' . $updatedSalesReturn->voucher_no . ') is updated successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
         }catch(Exception $e){
            DB::rollBack();
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destory($id)
    {
        $deleteSalesReturn = SalesReturn::findOrFail($id);
        DB::beginTransaction();
        try {
            $this->deductItemFromBranch($deleteSalesReturn->sales_return_details, Auth::user()->branch_id);
            $deleteSalesReturn->sales_return_details()->delete();
            $deleteSalesReturn->delete();

            DB::commit();
            $message = 'Purchase Return' . $deleteSalesReturn->voucher_no . ' is deleted successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
