<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use App\Models\Product;

//PUBLIC ROUTES
Route::post('/login', [AuthController::class, 'login']);
Route::post('/buy', [TransactionController::class, 'store']);

//PROTECTED ROUTES
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    //CRUD PRODUCTS, REFUND AND USERS
    Route::apiResource('products', ProductController::class)->middleware('role:admin,manager,finance'); //TODO
    Route::apiResource('users', UserController::class)->middleware('role:admin,manager'); //TODO
    Route::post('/transactions/{transaction}/refund', [TransactionController::class, 'refund'])->middleware('role:admin,finance'); //TODO

    //NON-ROLE CLIENT ROUTES
    Route::get('clients', [ClientController::class, 'index']); //TODO
    Route::get('clients/{client}/transactions', [ClientController::class, 'show']); //TODO

    //NON-ROLE TRANSACTION ROUTES
    Route::get('/transactions', [TransactionController::class, 'index']); //TODO
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']); //TODO

    //NON-ROLE GATEWAY ROUTES
    Route::prefix('gateways')->group(function () {
        Route::get('/', [GatewayController::class, 'index']); //TODO
        Route::patch('/{gateway}/change-status', [GatewayController::class, 'toggleStatus']); //TODO
        Route::patch('/{gateway}/priority', [GatewayController::class, 'updatePriority']); //TODO
    });

    //TODO REMOVE AFTER
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});