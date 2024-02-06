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
    LocationController,
    PaymentMethodController,
    PermissionControler,
    PermissionController,
    PurchaseController,
    PurchaseOrderController,
    PurchaseReturnController,
    ReceiveController,
    SalesController,
    SalesOrderController,
    SalesReturnController,
    SupplierController,
    UnitController,
    UnitConvertController,
    UserController
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
            "product sale list" => config('app.url') . "/api/products/{id}/sales_list",
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
                    "အလုံးစုံစာရင်း" => config('app.url') . "/api/purchases/summary",
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
                    "အလုံးစုံစာရင်း" => config('app.url') . "/api/sales/summary",
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
        ],
        "Customer Module" => [
            "customers_requests" => [
                "customers" => [
                    "customers List" => config('app.url') . "/api/customers",
                    "customers Create (POST)" => config('app.url') . "/api/customers",
                    "customer_update (PUT) " => config('app.url') . "/api/customers/{id}",
                    "customer_delete (DELETE)" => config('app.url') . "/api/customers/{id}",
                ],
                "customer detail" => [
                    "customer_detail (GET)" => config('app.url') . "/api/customers/{id}",
                ],
                "customer recent sale" => config('app.url') . "/api/customers/{customer_id}/recent_sales_list?order=asc&column=remain_amount&searchBy=&page=1&perPage=10",
                "customer recent order" => config('app.url') . "/api/customers/{customer_id}/recent_orders_list?order=asc&column=total_quantity&searchBy=&page=1&perPage=10",
                "customer payment history" => config('app.url') . "/api/customers/{customer_id}/recent_payment_history",
                "set up new Customer (POST)" => config('app.url') . "/api/customers",
                "pay debt" => [
                    'create_payment' => config('app.url') . "/api/customers/payment",
                    'getPaymentMethod (GET)' => config('app.url') . "/api/payment_methods",
                    'getCustomer List (GET)' => config('app.url') . "/api/customers?order=desc&column=join_date&searchBy=&page=1&perPage=10&city_id=&township_id=&hasDebt=&hasNoDebt=&report="
                ],
            ]
        ],
        "Supplier Module" => [
            "supplier list" => config('app.url') . "/api/suppliers?order=asc&column=join_date&searchBy=&page=1&perPage=10&city_id=&township_id=&hasDebt=&hasNoDebt=&report=",
            "Supplier Recent Purchase List" => config('app.url') . "/api/suppliers/{supplier_id}/recent_purchases_list?order=asc&column=remain_amount&searchBy=&page=1&perPage=10",
            "Supplier Recent Purchase Remain List" => config('app.url') . "/api/suppliers/{supplier_id}/recent_purchase_remain_list?order=asc&column=remain_amount&searchBy=&page=1&perPage=10",
            "Supplier Support API" => [
                "Supplier Detail" => config('app.url') . "/api/suppliers/{supplier_id}",
                "Supplier Create (POST)" => config('app.url') . "/api/suppliers",
                "Supplier Delete (DELETE)" => config('app.url') . "/api/suppliers"
            ],
        ],
        "User Profile and Settings" => [
            "Acc Setting" => [
                'Acc Setting' => config('app.url') . "/api/users/account_setting",
                "logOut" => config('app.url') . "/api/logout",
            ],
            "Activity History" => config('app.url') . "/api/users/activity_history",
            "Change passowrd" => config('app.url') . "/api/change_password"
        ],
        "User Management" => [
            "Role And Permission (admin view)" => [
                "Get Users (admin view)" => [
                    "User List (GET)" => config('app.url') . "/api/users?searchBy=",
                    "Create User (POST)" => config('app.url') . "/api/users",
                    "Edit User (PUT)" => config('app.url') . "/api/users/{id}",
                    "Delete User (DELETE)" => config('app.url') . "/api/users/{id}",
                ],
                "User Detail (ADMIN VIEW)" => [
                    "User Detail (GET)" => config('app.url') . "/api/users/{id}",
                    "User Activity" => config('app.url') . "/api/users/{id}/activity_history",
                    "Delete User (DELETE)" => config('app.url') . "/api/users/{id}",
                ],
                "Create New User (POST)" => config('app.url') . "/api/users",
                "Create New Branch (POST)" => config('app.url') . "/api/branches",
                "Branch Detail (GET)" => [
                    "Branch Detail (GET)" => config('app.url') . "/api/branches/{id}",
                    "Edit Branch (PUT)" => config('app.url') . "/api/branches/{id}",
                    "Delete Branch (DELETE)" => config('app.url') . "/api/branches/{id}"
                ],
                "Branch List" => [
                    "Branch List" => config('app.url') . "/api/branches?searchBy=",
                    "Create Branch (create)" => config('app.url') . "/api/branches",
                    "Update Branch (PUT)" => config('app.url') . "/api/branches/{id}",
                    "Delete Branch (DELETE)" => config('app.url') . "/api/branches/{id}",
                ],
                "Role List" => [
                    "Role List" => config('app.url') . "/api/roles?searchBy=",
                    "Delete Role (DELETE)" => config('app.url') . "/api/roles/{id}"
                ]
            ]
        ]
    ];
});

Route::get('/testing', [TestController::class, 'testing']);

Route::group(["middleware" => ['auth:sanctum']], function () {

    Route::post('/register', [AuthenticationController::class, 'registerUser']);
    Route::get('/get-current-user', [AuthenticationController::class, 'getCurrentUserRoleAndPermission']);
    Route::post('/change_password', [AuthenticationController::class, 'changeUserPassword']);

    Route::apiResource('roles', RoleController::class);
    Route::prefix('permissions')->group(function () {
        Route::get('', [PermissionController::class, 'index']);
    });

    Route::apiResource('branches', BranchController::class);
    Route::prefix('branches')->group(function () {
        Route::get('{branch_id}/user-list', [BranchController::class, 'getUserLists']);
    });
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('payment_methods', PaymentMethodController::class);

    Route::prefix('suppliers')->group(function() {
        Route::get('{id}/recent_purchases_list', [SupplierController::class, 'getSupplierPurchase']);
        Route::get('{id}/recent_purchase_remain_list', [SupplierController::class, 'getSupplierPurchaseByRemainAmount']);
    });
    Route::apiResource('suppliers', SupplierController::class);
    
    Route::prefix('customers')->group(function() {
        Route::get('{id}/recent_sales_list', [CustomerController::class, 'getCustomerSales']);
        Route::get('{id}/recent_orders_list', [CustomerController::class, 'getCustomerOrders']);
        Route::get('{id}/recent_payment_history', [CustomerController::class, 'getPaymentHistory']);
        
    });
    Route::apiResource('customers', CustomerController::class);

    Route::post('{type}/payment', [CustomerController::class, 'createCustomerPayment']);

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
        Route::get('products/{id}/sales_list', [InventoryController::class, 'productSalesListById']);
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
        Route::get('summary', [PurchaseController::class , 'getPurchaseSummary']);
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
        Route::get('summary', [SalesController::class , 'getSalesSummary']);
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
        Route::put('{id}', [SalesReturnController::class, 'update']);
    });

    Route::prefix('cities')->group(function () {
        Route::get('', [LocationController::class, 'getCities']);
        Route::post('', [LocationController::class, 'createCities']);
        Route::get('{id}', [LocationController::class, 'getCityById']);
        Route::put('{id}', [LocationController::class, 'updateCities']);
        Route::delete('{id}', [LocationController::class, 'deleteCity']);
    });

    Route::prefix('townships')->group(function () {
        Route::get('', [LocationController::class, 'getTownships']);
        Route::post('', [LocationController::class, 'createTownships']);
        Route::get('{id}', [LocationController::class, 'getTownshipById']);
        Route::put('{id}', [LocationController::class, 'updateTownships']);
        Route::delete('{id}', [LocationController::class, 'deleteTownships']);
    });

    Route::post('/logout', [AuthenticationController::class, 'logoutUser']);

    Route::prefix('users')->group(function() {
        Route::get('account_setting', [UserController::class, 'getAccountSetting']);
        Route::get('activity_history', [UserController::class, 'getActivityHistory']);
        
        // user management
        Route::get('', [UserController::class, 'index']);
        Route::post('', [UserController::class, 'create']);
        Route::get('{id}', [UserController::class, 'show']);
        Route::put('{id}', [UserController::class, 'update']);
        Route::delete('{id}', [UserController::class, 'destroy']);
        Route::get('{id}/activity_history' , [UserController::class, 'getUserActivityByUserId']);
    });
});
