<?php

use App\Http\Controllers\Api\{
    BranchController,
    AuthenticationController,
    CategoryController,
    CustomerController,
    DamageController,
    ItemController,
    ReceiveController,
    SupplierController,
    TransferController,
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


Route::get('/testing', [TestController::class, 'testing']);

Route::group(["middleware" => ['auth:sanctum']] , function () {

    Route::post('/register', [AuthenticationController::class, 'registerUser']);
    Route::get('/get-current-user', [AuthenticationController::class, 'getCurrentUserRoleAndPermission']);

    Route::apiResource('roles', RoleController::class);
    Route::apiResource('branches', BranchController::class);
    Route::apiResource('categories', CategoryController::class);
    // Route::apiResource('subcategories', CategoryController::class);
  
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('customers', CustomerController::class);

    // item, unit
    Route::apiResource('units', UnitController::class);
    Route::apiResource('items', ItemController::class);

    // issue , receive, damage
    Route::apiResource('transfers', TransferController::class);
    Route::apiResource('receives', ReceiveController::class);
    Route::apiResource('damages', DamageController::class);
});






