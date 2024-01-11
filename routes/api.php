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
    ReceiveController,
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
                "ဂိုထောင်မှတ်တမ်း" => config('app.url') . "/api/inventories/issues-receives-damages?order=desc&column=total_quantity&searchBy=&page=1&perPage=10",
            ],
            "receive/issue detail" => [
                "issue_detail" => config('app.url') . "/api/issues/{id}",
                "receive_detail" => config('app.url') . "/api/receives/{id}"
            ],
            "product_list" => [
                "ထုတ်ကုန်များ" => "UNDER CONSTRUCTION",
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
            "product purchase list" => "UNDER CONSTRUCTION",
            "product sale list" => "UNDER CONSTRUCTION",
            "stock history" => "UNDER CONSTRUCTION",
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
            "purchase_requests" =>[
                "purchase" => [
                "အလုံးစုံစာရင်း" => "UNDER CONSTRUCTION",
                "ဝယ်ယူမှုမှတ်တမ်း" => "UNDER CONSTRUCTION",
            ],
            "purchase detail" => config('app.url') . "/api/purchases/{id}",
            "create purchase" => config('app.url') . "/api/purchases",
            "total purchase List" => config('app.url') . "/api/purchases/total_purchase_list?order=desc&column=remain_amount&searchBy=&page=1&perPage=10&startDate=2024-01-01 00:00:00&endDate=2024-01-09 23:59:59&supplierId=1",
            "create_purchase_orders (POST)" => config('app.url') . "/api/purchase_orders",
            ]
        ]
        // "product_detail (GET)" => config('app.url') . "/api/items",
        // "unit_converts" => [
        //     "get_last_5_rows_convert (GET)" => config('app.url') . "/api/unit_converts",
        //     "create_unit_convert (POST)" => config('app.url') . "/api/unit_converts",
        //     "delete_unit_convert (DELETE)" => config('app.url') . "/api/unit_converts",
        // ],
        // "purchases" => [
        //     "create_purchase (POST)" => config('app.url') . "/api/purchases",
        // ]
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
    });
    Route::apiResource('issues', IssueController::class);
    Route::apiResource('receives', ReceiveController::class);
    Route::apiResource('damages', DamageController::class);

    // get issues and receives of branch
    Route::get('transfers', [IssueController::class, 'getIssuesReceivesAndDamages']);

    // unit conversion 
    Route::apiResource('unit_converts', UnitConvertController::class);

    
    // core modules
    Route::prefix('purchases')->group(function () { 
        Route::get('total_purchase_list', [PurchaseController::class, 'getTotalPurchaseList']);
    });
    Route::apiResource('purchases', PurchaseController::class);

    Route::prefix('purchase_orders')->group(function () {
        Route::post('', [PurchaseOrderController::class, 'create']);
    });
});
