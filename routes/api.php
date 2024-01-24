<?php

use App\Http\Controllers\Api\{
    BranchController,
    AuthenticationController,
    CategoryController,
    CustomerController,
    DamageController,
    InventoryController,
    IssueController,
    ItemController,
    PurchaseController,
    PurchaseOrderController,
    PurchaseReturnController,
    ReceiveController,
    SalesController,
    SalesOrderController,
    SalesReturnController,
    SupplierController,
    UnitController,
    UnitConvertController
};
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\RoleController;
use App\Models\Inventory;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// login route
Route::post('/login', [AuthenticationController::class, 'loginUser']);

Route::post('/central_links', function () {
    return [
        "INVENTORY_MODULE" => [
            "inventory" => [
                "အလုံးစုံစာရင်း" => config('app.url') . "/api/inventories/summary",
                "ဂိုထောင်မှတ်တမ်း" => [
                    'table_url' => config('app.url') . "/api/inventories/issues-receives-damages?order=desc&column=total_quantity&searchBy=&page=1&perPage=10",
                    'edit_url' => [
                        "issue" => config('app.url') . "/api/issues/{id}",
                        "receive" => config('app.url') . "/api/receives/{id}",
                        "damage" => config('app.url') . "/api/damages/{id}",
                    ],
                ],
            ],
            "receive/issue detail" => [
                "issue_detail" => config('app.url') . "/api/issues/{id}",
                "receive_detail" => config('app.url') . "/api/receives/{id}"
            ],
            "product_list" => [
                "ထုတ်ကုန်များ" => config('app.url') . "/api/items?searchBy=&order=desc&column=quantity",
            ],
            "damage/lost product list" => [
                "ပျက်စီးထုတ်ကုန်များ" => config('app.url') . "/api/damages?searchBy=&order=desc&column=quantity&perPage=10&page=1"
            ],
            "less stock list" => [
                "Stock နည်းနေသောထုတ်ကုန်များ" => config('app.url') . "/api/inventories/getlowstock?perPage=10&page=1&order=asc&column=quantity"
            ],
            "item issue,receive list" => [
                "ပစည်းလွှဲပြောင်း နှင့်လက်ခံစာရင်း" => config('app.url') . "/api/transfers?order=desc&column=total_quantity&searchBy=&page=1&perPage=10"
            ],
            "product detail" => config('app.url') . "/api/items/{id}",
            "product purchase list" => config('app.url') . "/api/products/{id}/purchase_list",
            "product sale list" => "UNDER CONSTRUCTION",
            "stock history" =>  config('app.url') . "/api/products/{id}/stock_history_list",
            "Unit Conversion" => [
                "get Item By Code" => config('app.url') . '/api/items/code/{item_code}',
                "ယူနစ်ကူးပြောင်းမှူမှတ်တမ်း (GET)" => config('app.url') . '/api/unit_converts',
                "create Unit Conversion (POST)" => config('app.url') . '/api/unit_converts'
            ],
            "set up new product" => [
                "get Item By Code (GET)" => config('app.url') . '/api/items/code/{item_code}',
                "ထုတ်ကုန်အသစ်သတ်မှတ် (POST)" => config('app.url') . '/api/items',
            ],
            "add new item receive" => [
                "get Item By Code (GET)" => config('app.url') . '/api/items/code/{item_code}',
                "ပစည်းလက်ခံစာရင်းသစ်သတ်မှတ် (POST)" => config('app.url') . '/api/receives'
            ],
            "add new damage" => [
                "get Item By Code (GET)" => config('app.url') . '/api/items/code/{item_code}',
                "ပျက်စီးထုတ်ကုန်စာရင်းသစ်သတ်မှတ် (POST)" => config('app.url') . '/api/damages'
            ],
            "add new item issue" => [
                "get Item By Code (GET)" => config('app.url') . '/api/items/code/{item_code}',
                "ပစည်းပို့စာရင်းသစ်သတ်မှတ် (POST)" => config('app.url') . '/api/issues'
            ],
        ],
        "PURCHASE_MODULE" => [
            "purchase_requests" => [
                "purchase" => [
                    "အလုံးစုံစာရင်း" => "UNDER CONSTRUCTION",
                    "ဝယ်ယူမှုမှတ်တမ်း" => config('app.url') . "/api/purchases?order=desc&column=remain_amount&searchBy=&page=1&perPage=10&startDate=&endDate=&supplier_id=&payment=",
                ],
                "purchase detail" => config('app.url') . "/api/purchases/{id}",
                "create purchase" => config('app.url') . "/api/purchases",
                "total purchase List" => config('app.url') . "/api/purchases/total_purchase_list?order=desc&column=remain_amount&searchBy=&page=1&perPage=10&startDate=2024-01-01 00:00:00&endDate=2024-01-09 23:59:59&supplierId=1",
                "total order List" => config('app.url') . "/api/purchase_orders",
                "create_purchase_orders (POST)" => config('app.url') . "/api/purchase_orders",
                "update_purchase_orders (PUT)" => config('app.url') . "/api/purchase_orders/{id}",
                "delete_purchase_orders (DELETE)" => config('app.url') . "/api/purchase_orders/{id}",
                "purchase return list" => "UNDER CONSTRUCTION",
                "purchase return detail" => "UNDER CONSTRUCTION",
                "create purchase return" => [
                    "pre_form (GET)" => config('app.url') . "/api/purchases/{id}/pre_return_form_data",
                    "Create form (POST)" => config('app.url') . '/api/purchase_returns',
                ],
                "update purchase return (PUT)" => "UNDER CONSTRUCTION",
                "delete purchase return (DELETE)" => "UNDER CONSTRUCTION",
            ]
        ],
        // "product_detail (GET)" => config('app.url') . "/api/items",
        // "unit_converts" => [
        //     "get_last_5_rows_convert (GET)" => config('app.url') . "/api/unit_converts",
        //     "create_unit_convert (POST)" => config('app.url') . "/api/unit_converts",
        //     "delete_unit_convert (DELETE)" => config('app.url') . "/api/unit_converts",
        // ],
        // "purchases" => [
        //     "create_purchase (POST)" => config('app.url') . "/api/purchases",
        // ]
        "Sale Module" => [
            "sales_requests" => [
                "sales" => [
                    "အလုံးစုံစာရင်း" => "UNDER CONSTRUCTION",
                    "အရောင်းမှတ်တမ်း" => config('app.url') . "/api/sales?order=desc&column=remain_amount&searchBy=&page=1&perPage=10&startDate=&endDate=&supplier_id=&payment=",
                ],
                "sale_detail" => config('app.url') . "/api/sales/{id}",
                "create sales (POST)" => config('app.url') . "/api/sales",
                "total sales List" => config('app.url') . "/api/sales/total_sales_list?order=desc&column=remain_amount&searchBy=&page=1&perPage=10&startDate=2024-01-01 00:00:00&endDate=2024-01-09 23:59:59&supplierId=1",
                "total order List" => config('app.url') . "/api/sales_orders",
                "create_sales_orders (POST)" => config('app.url') . "/api/sales_orders",
                "update_sales_orders (PUT)" => config('app.url') . "/api/sales_orders/{id}",
                "delete_sales_orders (DELETE)" => config('app.url') . "/api/sales_orders/{id}",
                "sales return list" => config('app.url') . "/api/sales_returns?searchBy=&order=desc&column=pay_amount&startDate&endDate&customer_id&report=",
                "sales return detail" => config('app.url') . "/api/sales_returns/1",
                "create purchase return" => [
                    "pre_form (GET)" => config('app.url') . "/api/purchases/{id}/pre_return_form_data",
                    "Create form (POST)" => config('app.url') . '/api/purchase_returns',
                ],
                "update sales return (PUT)" => "UNDER CONSTRUCTION",
                "delete sales return (DELETE)" => "UNDER CONSTRUCTION",
                
            ]
        ]
    ];
});


Route::get('/testing', [TestController::class, 'testing']);

Route::group(["middleware" => ['auth:sanctum']], function () {

    Route::post('/register', [AuthenticationController::class, 'registerUser']);
    Route::get('/get-current-user', [AuthenticationController::class, 'getCurrentUserRoleAndPermission']);

    Route::apiResource('roles', RoleController::class);
    Route::apiResource('branches', BranchController::class);
    Route::prefix('branches')->group(function () {
        Route::get('{branch_id}/user-list', [BranchController::class, 'getUserLists']);
    });
    Route::apiResource('categories', CategoryController::class);
    // Route::apiResource('subcategories', CategoryController::class);

    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('customers', CustomerController::class);

    // item, unit
    Route::apiResource('units', UnitController::class);
    Route::apiResource('items', ItemController::class);
    Route::prefix('items')->group(function () {
        Route::get('code/{item_code}', [ItemController::class, 'showByCode']);
    });

    // issue , receive, damage, inventory
    Route::prefix('inventories')->group(function () {
        Route::get('getlowstock', [InventoryController::class, 'getLowStockInventories']);
        // get issues, receives and damages of branch
        Route::get('issues-receives-damages', [InventoryController::class, 'getIssuesReceivesAndDamages']);
        // inventories summary
        Route::get('summary', [InventoryController::class, 'getInventorySummary']);
        Route::get('products/{id}/stock_history_list', [InventoryController::class, 'getStockHistory']);
        Route::get('products/{id}/purchase_list', [InventoryController::class, 'productPurchaseListById']);
    });
    Route::apiResource('issues', IssueController::class);
    Route::apiResource('receives', ReceiveController::class);
    Route::apiResource('damages', DamageController::class);

    // get issues and receives of branch
    Route::get('transfers', [IssueController::class, 'getIssuesReceives']);

    // unit conversion 
    Route::apiResource('unit_converts', UnitConvertController::class);

    // core modules
    Route::prefix('purchases')->group(function () {
        Route::get('total_purchase_list', [PurchaseController::class, 'getTotalPurchaseList']);
        Route::get('{id}/pre_return_form_data', [PurchaseController::class, 'getPreReturnFormData']);
    });
    Route::apiResource('purchases', PurchaseController::class);

    Route::prefix('purchase_orders')->group(function () {
        Route::get('', [PurchaseOrderController::class, 'index']);
        Route::post('', [PurchaseOrderController::class, 'create']);
        Route::get('{id}', [PurchaseOrderController::class, 'detail']);
        Route::put('{id}', [PurchaseOrderController::class, 'update']);
        Route::delete('{id}', [PurchaseOrderController::class, 'delete']);
    });

    Route::apiResource('purchase_returns', PurchaseReturnController::class);

    // sales
    Route::prefix('sales')->group(function () {
        Route::get('total_sales_list', [SalesController::class, 'getTotalSalesList']);
        Route::get('{id}/pre_return_form_data', [PurchaseController::class, 'getPreReturnFormData']);
    });
    Route::apiResource('sales', SalesController::class);
    // sales order
    Route::prefix('sales_orders')->group(function () {
        Route::get('', [SalesOrderController::class, 'index']);
        Route::post('', [SalesOrderController::class, 'create']);
        Route::get('{id}', [SalesOrderController::class, 'detail']);
        Route::put('{id}', [SalesOrderController::class, 'update']);
        Route::delete('{id}', [SalesOrderController::class, 'delete']);
    });

    Route::prefix('sales_returns')->group(function () {
        Route::get('', [SalesReturnController::class, 'index']);
        Route::post('', [SalesReturnController::class, 'create']);
        Route::get('{id}', [SalesReturnController::class, 'show']);
        Route::post('{id}', [SalesReturnController::class, 'update']);
    });
});
