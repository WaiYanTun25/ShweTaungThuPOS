<?php

use App\Http\Controllers\Api\{
    BranchController,
    AuthenticationController,
    CategoryController
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

Route::post('/register', [AuthenticationController::class, 'registerUser']);

Route::get('/testing', [TestController::class, 'testing']);

Route::group(["middleware" => ['auth:sanctum']] , function () {

    Route::middleware(('can:get-roles,create-role,edit-role,delete-role'))->group(function() {
        Route::apiResource('roles', RoleController::class);
    });

    Route::apiResource('branches', BranchController::class);

    Route::apiResource('categories', CategoryController::class);

    Route::apiResource('subcategories', CategoryController::class);
});





