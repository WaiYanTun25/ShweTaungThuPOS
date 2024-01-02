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
    ReceiveController,
    SupplierController,
    UnitController
};
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\RoleController;
use Illuminate\Http\Request;
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
        "product_detail" => config('app.url'). "/api/items"
    ];
});


Route::get('/testing', [TestController::class, 'testing']);

Route::group(["middleware" => ['auth:sanctum']] , function () {

    Route::post('/register', [AuthenticationController::class, 'registerUser']);
    Route::get('/get-current-user', [AuthenticationController::class, 'getCurrentUserRoleAndPermission']);

    Route::apiResource('roles', RoleController::class);
    Route::apiResource('branches', BranchController::class);
    Route::prefix('branches')->group(function() {
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
    Route::prefix('inventories')->group(function() {
        Route::get('getlowstock', [InventoryController::class, 'getLowStockInventories']);
    });
    Route::apiResource('issues', IssueController::class);
    Route::apiResource('receives', ReceiveController::class);
    Route::apiResource('damages', DamageController::class);

    // get issues and receives of branch
    Route::get('transfers', [IssueController::class, 'getIssuesReceivesAndDamages']);
});






