<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\RolePermissionController;

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

Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    // Define API routes for the Role resource
    Route::apiResource('/roles', RoleController::class);

    // Define API routes for the Person resource
    Route::apiResource('/people', PersonController::class);

    // Define API routes for the User resource
    Route::apiResource('/users', UserController::class);

    // Define API routes for the Permission resource
    Route::apiResource('/permissions', PermissionController::class);

    // Define API routes for the UserRole resource
    Route::apiResource('user-roles', UserRoleController::class);

    // Define API routes for the RolePermission resource
    Route::apiResource('role-permissions', RolePermissionController::class);
});
