<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IssueReceiveDamageResource;
use App\Http\Resources\LowStockResource;
use App\Http\Resources\PurchaseListByProductIdResource;
use App\Http\Resources\SalesListByProductIdResource;
use App\Http\Resources\StockHistroyResource;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\ItemUnitDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Exception;
use stdClass;
use App\Models\Issue;
use App\Models\Damage;
use App\Models\Receive;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Sale;
use App\Models\Scopes\BranchScope;
use Carbon\Carbon;

class InventoryController extends ApiBaseController
{
    public function getLowStockInventories(Request $request)
    {

        $perPage = $request->query('perPage', 10);
        $search = $request->query('searchBy');
        // Handle order and column
        $order = $request->query('order', 'asc'); // default to asc if not provided
        $column = $request->query('column', 'transaction_date'); // default to id if not provided

        //Filter
        $category_id = $request->query('category_id');
        $supplier_id = $request->query('supplier_id');
        $reorder_from = $request->query('reorder_from');
        $reorder_to = $request->query('reorder_to');

        if (Auth::user()->branch_id != 0) {
            $itemDetails = ItemUnitDetail::with('item');

            // filter data
            if ($reorder_from && $reorder_to) {
                $itemDetails->where('reorder_period', '>=', $reorder_from)
                    ->where('reorder_period', '<=', $reorder_to);
            }
            if ($category_id) {
                $itemDetails->whereHas('item', function ($q) use ($category_id) {
                    $q->where('category_id', $category_id);
                });
            }
            if ($supplier_id) {
                $itemDetails->whereHas('item', function ($q) use ($supplier_id) {
                    $q->where('supplier_id', $supplier_id);
                });
            }

            $itemDetails->join('inventories', function ($join) {
                $join->on('item_unit_details.item_id', '=', 'inventories.item_id')
                    ->on('item_unit_details.unit_id', '=', 'inventories.unit_id')
                    ->where('inventories.branch_id', Auth::user()->branch_id)
                    ->where('inventories.quantity', '<', DB::raw('item_unit_details.reorder_level'));
            })


                ->leftJoin('transfer_details', function ($join) {
                    $join->on('item_unit_details.item_id', '=', 'transfer_details.item_id')
                        ->on('item_unit_details.unit_id', '=', 'transfer_details.unit_id')
                        ->where('transfer_details.voucher_no', 'like', 'INV-R%')
                        ->whereRaw('transfer_details.id = (SELECT MAX(id) FROM transfer_details WHERE item_unit_details.item_id = transfer_details.item_id AND item_unit_details.unit_id = transfer_details.unit_id AND transfer_details.voucher_no LIKE "INV-R%")');
                })


                ->leftJoin('receives', function ($join) {
                    $join->on('transfer_details.voucher_no', '=', 'receives.voucher_no');
                })

                // Only add the join and search condition if $search is provided
                ->when($search, function ($query) use ($search) {
                    $query->join('items', 'item_unit_details.item_id', '=', 'items.id')
                        ->where('items.item_name', 'like', '%' . $search . '%')
                        ->orWhere('items.item_code', 'like', '%' . $search . '%');
                })
                ->select(
                    // 'receives.voucher_no as receives_voucher_no',
                    // 'receives.transaction_date as receives_transaction_date',
                    'receives.transaction_date',
                    'item_unit_details.*',
                    'inventories.quantity as quantity',
                    'inventories.branch_id as branch_id'
                )

                ->orderBy($column, $order);

            if ($request->query('report') == "True") {
                $results = $itemDetails->get();
                $resourceCollection = new LowStockResource($results, true);

                return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);
            }

            $itemDetails =  $itemDetails->paginate($perPage);

            $result = new LowStockResource($itemDetails);
        } else {
            $itemDetails = ItemUnitDetail::with('item');

            // filter Data
            if ($reorder_from && $reorder_to) {
                $itemDetails->where('reorder_period', '>=', $reorder_from)
                    ->where('reorder_period', '<=', $reorder_to);
            }
            if ($category_id) {
                $itemDetails->whereHas('item', function ($q) use ($category_id) {
                    $q->where('category_id', $category_id);
                });
            }
            if ($category_id) {
                $itemDetails->whereHas('item', function ($q) use ($category_id) {
                    $q->where('category_id', $category_id);
                });
            }

            $itemDetails->join('inventories', function ($join) {
                $join->on('item_unit_details.item_id', '=', 'inventories.item_id')
                    ->on('item_unit_details.unit_id', '=', 'inventories.unit_id')
                    ->where('inventories.quantity', '<', DB::raw('item_unit_details.reorder_level'));
            })

                ->leftJoin('transfer_details', function ($join) {
                    $join->on('item_unit_details.item_id', '=', 'transfer_details.item_id')
                        ->on('item_unit_details.unit_id', '=', 'transfer_details.unit_id')
                        ->where('transfer_details.voucher_no', 'like', 'INV-R%')
                        ->whereRaw('transfer_details.id = (SELECT MAX(id) FROM transfer_details WHERE item_unit_details.item_id = transfer_details.item_id AND item_unit_details.unit_id = transfer_details.unit_id AND transfer_details.voucher_no LIKE "INV-R%")');
                })

                ->leftJoin('receives', function ($join) {
                    $join->on('transfer_details.voucher_no', '=', 'receives.voucher_no');
                })

                // Only add the join and search condition if $search is provided
                ->when($search, function ($query) use ($search) {
                    $query->join('items', 'item_unit_details.item_id', '=', 'items.id')
                        ->where('items.item_name', 'like', '%' . $search . '%')
                        ->orWhere('items.item_code', 'like', '%' . $search . '%');
                })
                ->select(
                    'receives.transaction_date',
                    'item_unit_details.*',
                    'inventories.quantity as quantity',
                    'inventories.branch_id as branch_id'
                );

            if ($request->query('report') == "True") {
                $results = $itemDetails->get();
                $resourceCollection = new LowStockResource($results, true);

                return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);
            }
            $itemDetails = $itemDetails->paginate($perPage); 
            
            // return $itemDetails;
            $result = new LowStockResource($itemDetails);
        }

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $result);
    }

    public function getIssuesReceivesAndDamages(Request $request)
    {
        // filter query request
        $reason = $request->query('reason');
        $reasonArray = explode(",", $reason);

        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        $withDateFilter = function ($query) use ($startDate, $endDate) {
            if ($startDate && $endDate) {
                $query->whereDate('transaction_date', '>=', $startDate)
                    ->whereDate('transaction_date', '<=', $endDate);
            }
        };

        $getIssue = Issue::with(['transfer_details', 'transfer_details.unit', 'transfer_details.item'])
            ->select(['id', 'voucher_no', 'total_quantity', 'transaction_date', DB::raw("to_branch_id as branch_id"), DB::raw("'ISSUE' as type")])
            ->tap($withDateFilter);

        $getReceive = Receive::with(['transfer_details', 'transfer_details.unit', 'transfer_details.item'])
            ->select(['id', 'voucher_no', 'total_quantity', 'transaction_date', DB::raw("from_branch_id as branch_id"), DB::raw("'RECEIVE' as type")])
            ->tap($withDateFilter);

        $getDamage = Damage::with(['transfer_details', 'transfer_details.unit', 'transfer_details.item'])
            ->select(['id', 'voucher_no', 'total_quantity', 'transaction_date', DB::raw("branch_id as branch_id"), DB::raw("'DAMAGE' as type")])
            ->tap($withDateFilter);

        $search = $request->query('searchBy');

        if ($search) {
            $getIssue->where('voucher_no', 'like', "%$search%")
                ->orWhereHas('transfer_details.item', function ($query) use ($search) {
                    $query->where('item_name', 'like', "%$search%");
                });

            $getReceive->where('voucher_no', 'like', "%$search%")
                ->orWhereHas('transfer_details.item', function ($query) use ($search) {
                    $query->where('item_name', 'like', "%$search%");
                });

            $getDamage->where('voucher_no', 'like', "%$search%")
                ->orWhereHas('transfer_details.item', function ($query) use ($search) {
                    $query->where('item_name', 'like', "%$search%");
                });
        }

        if (in_array('ISSUE', $reasonArray) && in_array('RECEIVE', $reasonArray) && in_array('DAMAGE', $reasonArray)) {
            $results = $getIssue->union($getReceive)->union($getDamage);
        } elseif (in_array('ISSUE', $reasonArray) && in_array('RECEIVE', $reasonArray)) {
            $results = $getIssue->union($getReceive);
        } elseif (in_array('ISSUE', $reasonArray) && in_array('DAMAGE', $reasonArray)) {
            $results = $getIssue->union($getDamage);
        } elseif (in_array('RECEIVE', $reasonArray) && in_array('DAMAGE', $reasonArray)) {
            $results = $getReceive->union($getDamage);
        } elseif (in_array('ISSUE', $reasonArray)) {
            $results = $getIssue;
        } elseif (in_array('RECEIVE', $reasonArray)) {
            $results = $getReceive;
        } elseif (in_array('DAMAGE', $reasonArray)) {
            $results = $getDamage;
        } else {
            // Default case when none of the conditions match
            $results = $getIssue->union($getReceive)->union($getDamage);
        }

        // if ($startDate && $endDate) {
        //     $results->whereBetween('transaction_date', [$startDate, $endDate]);
        // }

        // Handle order and column
        $order = $request->query('order', 'asc'); // default to asc if not provided
        $column = $request->query('column', 'transaction_date'); // default to id if not provided

        $results = $results->orderBy($column, $order);

        // Paginate the results
        $perPage = $request->query('perPage', 10);
        $results = $results->paginate($perPage);

        $resourceCollection = new IssueReceiveDamageResource($results);

        // $results = $getIssue->union($getReceive)->union($getDamage)->orderBy($column, $order)->paginate($perPage);
        // $resourceCollection = new TransferResourceCollection($results);

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);
    }

    public function getInventorySummary(Request $request)
    {
        // $currentMonth = Carbon::now()->month;
        // $currentYear = Carbon::now()->year;

        $user_branch_id = Auth::user()->branch_id;
        if ($user_branch_id != 0) {
            $countItem = Inventory::where('branch_id', $user_branch_id)->sum('quantity');
        } else {
            $countItem = Inventory::sum('quantity');
        }

        $countIssueWithinOneMonth = Issue::count();
            // whereYear('transaction_date', $currentYear)
            // ->whereMonth('transaction_date', $currentMonth)
            // ->count();

        $countReceiveWithinOneMonth = Receive::count();
            // whereYear('transaction_date', $currentYear)
            // ->whereMonth('transaction_date', $currentMonth)
            // ->count();

        $coutnDamgeWithingOneMonth = Damage::count();
            // whereYear('transaction_date', $currentYear)
            // ->whereMonth('transaction_date', $currentMonth)
            // ->count();

        $countLowStockWithinOneMonth = ItemUnitDetail::with('item')
            ->join('inventories', function ($join) {
                $join->on('item_unit_details.item_id', '=', 'inventories.item_id')
                    ->on('item_unit_details.unit_id', '=', 'inventories.unit_id')
                    ->when(Auth::user()->branch_id !== 0, function ($query) {
                        $query->where('inventories.branch_id', Auth::user()->branch_id);
                    })
                    ->where('inventories.quantity', '<', DB::raw('item_unit_details.reorder_level'))
                    ->where('inventories.quantity', '>', 0);
            })->count();

        $countOutOfStock = Inventory::when(Auth::user()->branch_id !== 0, function ($query) {
            $query->where('inventories.branch_id', Auth::user()->branch_id);
        })
        ->where('quantity', '<=', 0)
        ->count();


        $result = new stdClass;
        $result->total_items = $countItem;
        $result->total_transfers = [
            'total_receive' => $countReceiveWithinOneMonth,
            'total_issue' => $countIssueWithinOneMonth
        ];
        $result->total_damages = $coutnDamgeWithingOneMonth; // Typo in variable name corrected
        $result->total_lowstocks = [
            'total_lowstock' => $countLowStockWithinOneMonth,
            'total_outstock' => $countOutOfStock,
        ];

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $result);
    }
    public function productPurchaseListById(Request $request, $id)
    {
        $perPage = $request->query('perPage', 10);
        $search = $request->query('searchBy');
        $order = $request->query('order', 'asc');
        $column = $request->query('column', 'id');

        // filter
        $supplier_id = $request->query('supplier_id');
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');
        $payment_status = $request->query('payment_status');

        $purchase_products = Purchase::with(['purchase_details' => function ($query) use ($id) {
            $query->where('item_id', $id);
        }])->whereHas('purchase_details', function ($query) use ($id) {
            $query->where('item_id', $id);
        })->withSum('purchase_details as total_product_quantity', 'quantity');

        if (Auth::user()->branch_id != 0) {
            $purchase_products->where('branch_id', Auth::user()->branch_id);
        }

        if ($startDate && $endDate) {
            // $purchase_products->whereBetween('purchase_date', [$startDate, $endDate]);
            $purchase_products->whereDate('purchase_date', '>=', $startDate)
                ->whereDate('purchase_date', '<=', $endDate);
        }
        if ($supplier_id) {
            $purchase_products->where('supplier_id', $supplier_id);
        }

        if (!empty($payment_status)) {
            $paymentArray = explode(",", $payment_status);

            // Check if $paymentArray is not empty
            if (!empty($paymentArray)) {
                $purchase_products->whereIn('payment_status', $paymentArray);
            }
        }

        if ($search) {
            $purchase_products->where(function ($query) use ($search) {
                $query->whereHas('supplier', function ($subquery) use ($search) {
                    $subquery->where('name', 'like', '%' . $search . '%');
                })
                    ->orWhere('voucher_no', 'like', '%' . $search . '%');
            });
        }

        if ($request->query('report') == "True") {
            $results = $purchase_products->orderBy($column, $order)->get();
            $resourceCollection = new PurchaseListByProductIdResource($results, True);
            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
        }

        $results = $purchase_products->orderBy($column, $order)->paginate($perPage);

        $resourceCollection = new PurchaseListByProductIdResource($results);

        return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
    }

    public function productSalesListById(Request $request, $id)
    {
        $perPage = $request->query('perPage', 10);
        $search = $request->query('searchBy');
        $order = $request->query('order', 'asc');
        $column = $request->query('column', 'id');

        // filter
        $customer_id = $request->query('customer_id');
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');
        $payment_status = $request->query('payment_status');

        $sales_products = Sale::with(['sales_details' => function ($query) use ($id) {
            $query->where('item_id', $id);
        }])->whereHas('sales_details', function ($query) use ($id) {
            $query->where('item_id', $id);
        })->withSum('sales_details as total_product_quantity', 'quantity');

        if (Auth::user()->branch_id != 0) {
            $sales_products->where('branch_id', Auth::user()->branch_id);
        }

        if ($startDate && $endDate) {
            $sales_products->whereDate('sales_date', '>=', $startDate)
                ->whereDate('sales_date', '<=', $endDate);
        }
        if ($customer_id) {
            $sales_products->where('customer_id', $customer_id);
        }

        if (!empty($payment_status)) {
            $paymentArray = explode(",", $payment_status);

            // Check if $paymentArray is not empty
            if (!empty($paymentArray)) {
                $sales_products->whereIn('payment_status', $paymentArray);
            }
        }

        if ($search) {
            $sales_products->where(function ($query) use ($search) {
                $query->whereHas('customer', function ($subquery) use ($search) {
                    $subquery->where('name', 'like', '%' . $search . '%');
                })
                    ->orWhere('voucher_no', 'like', '%' . $search . '%');
            });
        }

        if ($request->query('report') == "True") {
            $results = $sales_products->orderBy($column, $order)->get();
            $resourceCollection = new SalesListByProductIdResource($results, True);
            return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
        }

        $results = $sales_products->orderBy($column, $order)->paginate($perPage);

        $resourceCollection = new SalesListByProductIdResource($results);

        return $this->sendSuccessResponse('Success', Response::HTTP_OK, $resourceCollection);
    }

    public function getStockHistory(Request $request, $id)
    {
        $selectedYear = $request->query('selectedYear', now()->year);

        $getIssue = Issue::select([
            'issues.id', 
            'issues.voucher_no', 
            'issues.transaction_date', 
            DB::raw("issues.from_branch_id as branch_id"), 
            DB::raw("'ISSUE' as type"),
            DB::raw('SUM(transfer_details.quantity) as total_quantity')
            ])
            ->join('transfer_details', function ($join) use ($id) {
                $join->on('issues.voucher_no', '=', 'transfer_details.voucher_no')
                     ->where('transfer_details.item_id', '=', $id);
            })
            // ->with(['transfer_details' => function ($query) use ($id) {
            //     $query->where('item_id', $id);
            // }])
            // ->whereHas('transfer_details', function ($query) use ($id) {
            //     $query->where('item_id', $id);
            // })
            ->whereYear('transaction_date', $selectedYear)
            ->groupBy('issues.id', 'issues.voucher_no', 'issues.transaction_date', 'issues.from_branch_id');


        // old structure
        // $getReceive = Receive::select([
        //     'id', 'voucher_no', 'transaction_date', DB::raw("to_branch_id as branch_id"), 
        //     DB::raw("'RECEIVE' as type"),
        //     DB::raw('SUM(transfer_details.quantity) as total_quantity')
        //     ])
        //     ->with(['transfer_details' => function ($query) use ($id) {
        //         $query->where('item_id', $id);
        //     }])
        //     ->whereHas('transfer_details', function ($query) use ($id) {
        //         $query->where('item_id', $id);
        //     })->whereYear('transaction_date', $selectedYear);

        $getReceive = Receive::select([
            'receives.id',
            'receives.voucher_no',
            'receives.transaction_date',
            DB::raw('receives.to_branch_id as branch_id'),
            DB::raw("'RECEIVE' as type"),
            DB::raw('SUM(transfer_details.quantity) as total_quantity')
        ])
        ->join('transfer_details', function ($join) use ($id) {
            $join->on('receives.voucher_no', '=', 'transfer_details.voucher_no')
                 ->where('transfer_details.item_id', '=', $id);
        })
        ->whereYear('receives.transaction_date', $selectedYear)
        ->groupBy('receives.id', 'receives.voucher_no', 'receives.transaction_date', 'receives.to_branch_id');
        

        // old structure
        // $getDamage = Damage::select([
        //     'id', 'voucher_no', 'transaction_date', DB::raw("branch_id as branch_id"), 
        //     DB::raw("'DAMAGE' as type"),
        //     DB::raw('SUM(transfer_details.quantity) as total_quantity')
        //     ])
        //     ->with(['transfer_details' => function ($query) use ($id) {
        //         $query->where('item_id', $id);
        //     }])
        //     ->whereHas('transfer_details', function ($query) use ($id) {
        //         $query->where('item_id', $id);
        //     })->whereYear('transaction_date', $selectedYear);

        $getDamage = Damage::select([
            'damages.id',
            'damages.voucher_no',
            'damages.transaction_date',
            DB::raw('damages.branch_id as branch_id'),
            DB::raw("'DAMAGE' as type"),
            DB::raw('SUM(transfer_details.quantity) as total_quantity')
        ])
        ->join('transfer_details', function ($join) use ($id) {
            $join->on('damages.voucher_no', '=', 'transfer_details.voucher_no')
                 ->where('transfer_details.item_id', '=', $id);
        })
        ->whereYear('damages.transaction_date', $selectedYear)
        ->groupBy('damages.id', 'damages.voucher_no', 'damages.transaction_date', 'damages.branch_id');
        
        // old
        // $getPurchase = Purchase::
        //     select([
        //         'id', 'voucher_no', 'purchase_date as transaction_date', DB::raw("branch_id as branch_id"), 
        //         DB::raw("'PURCHASE' as type"),
        //         ])
        //     ->with(['purchase_details' => function ($query) use ($id) {
        //         $query->where('item_id', $id);
        //     }])
        //     ->whereHas('purchase_details', function ($query) use ($id) {
        //         $query->where('item_id', $id);
        //     })
        //     ->whereYear('purchase_date', $selectedYear);

        $getPurchase = Purchase::select([
            'purchases.id',
            'purchases.voucher_no',
            'purchases.purchase_date as transaction_date',
            DB::raw('purchases.branch_id as branch_id'),
            DB::raw("'PURCHASE' as type"),
            DB::raw('SUM(purchase_details.quantity) as total_quantity')
        ])
        ->join('purchase_details', function ($join) use ($id) {
            $join->on('purchases.id', '=', 'purchase_details.purchase_id')
                 ->where('purchase_details.item_id', '=', $id);
        })
        ->whereYear('purchases.purchase_date', $selectedYear)
        ->groupBy('purchases.id', 'purchases.voucher_no', 'purchases.purchase_date', 'purchases.branch_id');

        $getSale = Sale::select([
            'sales.id',
            'sales.voucher_no',
            'sales.sales_date as transaction_date',
            DB::raw('sales.branch_id as branch_id'),
            DB::raw("'SALES' as type"),
            DB::raw('SUM(sale_details.quantity) as total_quantity')
        ])
        ->join('sale_details', function ($join) use ($id) {
            $join->on('sales.id', '=', 'sale_details.sale_id')
                 ->where('sale_details.item_id', '=', $id);
        })
        ->whereYear('sales.sales_date', $selectedYear)
        ->groupBy('sales.id', 'sales.voucher_no', 'sales.sales_date', 'sales.branch_id');
        // if ($search) {
        //     $getIssue->where('voucher_no', 'like', "%$search%")
        //         // ->orWhere('transaction_date', 'like', "%$search%")
        //         // ->orWhere('total_quantity', 'like', "%$search%")
        //         ->orWhereHas('transfer_details.item', function ($query) use ($search) {
        //             $query->where('item_name', 'like', "%$search%");
        //         });

        //     $getReceive->where('voucher_no', 'like', "%$search%")
        //         // ->orWhere('transaction_date', 'like', "%$search%")
        //         // ->orWhere('total_quantity', 'like', "%$search%")
        //         ->orWhereHas('transfer_details.item', function ($query) use ($search) {
        //             $query->where('item_name', 'like', "%$search%");
        //         });

        //     $getDamage->where('voucher_no', 'like', "%$search%")
        //         // ->orWhere('transaction_date', 'like', "%$search%")
        //         // ->orWhere('total_quantity', 'like', "%$search%");
        //         ->orWhereHas('transfer_details.item', function ($query) use ($search) {
        //             $query->where('item_name', 'like', "%$search%");
        //         });
        // }

        

        // Handle order and column
        $order = $request->query('order', 'asc'); // default to asc if not provided
        $column = $request->query('column', 'transaction_date'); // default to id if not provided
        // Combine the results using union
        $perPage = $request->query('perPage', 10);
        // $results = $getIssue->union($getReceive)->orderBy($column, $order)->paginate($perPage);
        $results = $getIssue->union($getReceive)->union($getDamage)
            ->union($getPurchase)
            ->union($getSale)
            ->orderBy($column, $order)->paginate($perPage);

        $resourceCollection = new StockHistroyResource($results);

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $resourceCollection);
    }
}
