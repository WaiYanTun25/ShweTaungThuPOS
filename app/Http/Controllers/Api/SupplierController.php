<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierRequest;
use App\Http\Resources\SupplierDetailResource;
use App\Http\Resources\SupplierListResource;
use App\Http\Resources\SupplierRecentPurchaseListResource;
use App\Http\Resources\SupplierRecentRemainListResource;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Supplier;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SupplierController extends ApiBaseController
{
    public function __construct()
    {
        $checkPermission = request()->query('permission') === 'True';
        // Conditionally apply permission middleware
        if ($checkPermission) {
            $this->middleware('permission:supplier:read')->only('index', 'show');
            $this->middleware('permission:supplier:create')->only('store');
            $this->middleware('permission:supplier:edit')->only('update');
            $this->middleware('permission:supplier:delete')->only('delete');
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $getSupplier = Supplier::select('*',
            DB::raw('COALESCE(
                (SELECT SUM(COALESCE(s.remain_amount, 0)) FROM purchases s WHERE s.supplier_id = suppliers.id) -
                (SELECT COALESCE(SUM(p.pay_amount), 0) FROM payments p WHERE p.subject_id = suppliers.id AND p.type = "Supplier")
            , 0) as debt_amount'),
            DB::raw('COALESCE(
                (SELECT SUM(COALESCE(s.total_amount, 0)) FROM purchases s WHERE s.supplier_id = suppliers.id)
            , 0) as total_purchase_amount')
        );

        $search = $request->query('searchBy');

        // filters
        $cityID = $request->query('city_id');
        $townshipID = $request->query('township_id');
        $hasDebt = $request->query('hasDebt');
        $hasNoDebt = $request->query('hasNoDebt');

        // report 
        $report = $request->query('report');

        if ($cityID) {
            $getSupplier->where('city', $cityID);
        }
        if ($townshipID) {
            $getSupplier->where('township', $townshipID);
        }

        if($hasDebt && $hasNoDebt || !$hasDebt && !$hasNoDebt){ 
          
        } elseif ($hasDebt && !$hasNoDebt) {
            $getSupplier->having('debt_amount', '>' , 0);
        } else if(!$hasDebt && $hasNoDebt) {
            $getSupplier->having('debt_amount', '=' , 0);
        }

        if ($search) {
            $getSupplier->where('name', 'like', "%$search%")
                ->orWhere('phone_number', 'like', "%$search%")
                ->orWhere('township', 'like', "%$search%");
        }
        // Handle order and column
        $order = $request->query('order', 'asc'); // default to asc if not provided
        $column = $request->query('column', 'id'); // default to id if not provided
        $perPage = $request->query('perPage', 10);
        $getSupplier->orderBy($column, $order);

        if ($report == 'True') {
            $suppliers = $getSupplier->get();
            $getCollection = new SupplierListResource($suppliers, true);
            return $this->sendSuccessResponse('success', Response::HTTP_OK, $getCollection);
        }

        $suppliers = $getSupplier->paginate($perPage);
        $getCollection = new SupplierListResource($suppliers);

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $getCollection);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SupplierRequest $request)
    {
        try {
            $createdSupplier = new Supplier();
            $createdSupplier->name = $request->name;
            $createdSupplier->address = $request->address;
            $createdSupplier->phone_number = $request->phone_number;
            $createdSupplier->township = $request->township;
            $createdSupplier->city = $request->city;
            $createdSupplier->prefix = $request->prefix;
            $createdSupplier->save();

            $message = 'Supplier (' . $createdSupplier->name . ') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        } catch (Exception $e) {
            info($e->getMessage());
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $getSupplier = Supplier::findOrFail($id);
        $getCollection = new SupplierDetailResource($getSupplier);

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $getCollection);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SupplierRequest $request, string $id)
    {
        $updatedSupplier = Supplier::findOrFail($id);
        try {
            $updatedSupplier->name = $request->name;
            $updatedSupplier->address = $request->address;
            $updatedSupplier->phone_number = $request->phone_number;
            $updatedSupplier->township = $request->township;
            $updatedSupplier->city = $request->city;
            $updatedSupplier->prefix = $request->prefix;
            $updatedSupplier->save();

            $message = 'Supplier (' . $updatedSupplier->name . ') is updated successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        } catch (Exception $e) {
            info($e->getMessage());
            return $this->sendErrorResponse('Something went wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $supplier = Supplier::findOrFail($id);
        try {
            $checkSupplierExist = Item::where('supplier_id', $id)->first();

            if ($checkSupplierExist) {
                return $this->sendErrorResponse('There are related data with ' . $supplier->name, Response::HTTP_CONFLICT);
            }
            $supplier->delete();

            $message = 'Supplier (' . $supplier->name . ') is deleted successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        } catch (Exception $e) {
            info($e->getMessage());
            return $this->sendErrorResponse('Something went wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getSupplierPurchase(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);
        try {
            $supplierPurchase = Purchase::where('supplier_id', $id);

            $search = $request->query('searchBy');
            if ($search)
            {
                $supplierPurchase->where(function ($query) use ($search) {
                    $query->where('voucher_no', 'like', "%$search%")
                        ->orWhereHas('purchase_details', function ($detailsQuery) use ($search) {
                            $detailsQuery->whereHas('item', function ($itemQuery) use ($search) {
                                $itemQuery->where('item_name', 'like', "%$search%");
                            });
                        });
                });
            }
    
            $order = $request->query('order', 'desc'); // default to asc if not provided
            $column = $request->query('column', 'id'); // default to id if not provided
            $perPage = $request->query('perPage', 10);
    
            $supplierPurchase = $supplierPurchase->orderBy($column, $order)->paginate($perPage);
            $resourceCollection = new SupplierRecentPurchaseListResource($supplierPurchase);

            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
        }catch(Exception $e){
            return $this->sendErrorResponse('Error getting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function getSupplierPurchaseByRemainAmount(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);
        try {
            $supplierPurchase = Purchase::where('supplier_id', $id)
                            ->where('remain_amount', '>', 0);

            $search = $request->query('searchBy');
            if ($search)
            {
                $supplierPurchase->where(function ($query) use ($search) {
                    $query->where('voucher_no', 'like', "%$search%")
                        ->orWhereHas('purchase_details', function ($detailsQuery) use ($search) {
                            $detailsQuery->whereHas('item', function ($itemQuery) use ($search) {
                                $itemQuery->where('item_name', 'like', "%$search%");
                            });
                        });
                });
            }
    
            $order = $request->query('order', 'desc'); // default to asc if not provided
            $column = $request->query('column', 'id'); // default to id if not provided
            $perPage = $request->query('perPage', 10);
    
            $supplierPurchase = $supplierPurchase->orderBy($column, $order)->paginate($perPage);
            $resourceCollection = new SupplierRecentRemainListResource($supplierPurchase);

            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
        }catch(Exception $e){
            return $this->sendErrorResponse('Error getting item', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
