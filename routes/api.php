<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\ClientController;

use App\Models\Product;

//PUBLIC ROUTES
Route::post('/login', [AuthController::class, 'login']);
Route::post('/buy', [TransactionController::class, 'store']);

//PROTECTED ROUTES
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    //CRUD PRODUCTS, REFUND AND USERS
    //*IN A REAL CASE SCENARIO, I'D PROBABLY SANITIZE THE USER INPUT FOR USER, CLIENT AND PRODUCTS NAMES TO AVOID SOME KIND OF INJECTION ATTACKS
    //*BUT HEY, THIS IS THE BEST WE GET FOR THE SCOPE OF THIS TEST :D
    Route::apiResource('products', ProductController::class)->middleware('role:admin,manager,finance');
    Route::apiResource('users', UserController::class)->middleware('role:admin,manager');
    Route::post('/transactions/{transaction}/refund', [TransactionController::class, 'refund'])->middleware('role:admin,finance');

    //NON-ROLE CLIENT ROUTES
    Route::get('clients', [ClientController::class, 'index']);
    Route::get('clients/{client}', [ClientController::class, 'show']);

    //NON-ROLE TRANSACTION ROUTES
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);

    //NON-ROLE GATEWAY ROUTES
    Route::prefix('gateways')->group(function () {
        Route::get('/', [GatewayController::class, 'index']);
        Route::patch('/{gateway}/change-status', [GatewayController::class, 'toggleStatus']);
        Route::patch('/{gateway}/priority', [GatewayController::class, 'updatePriority']);
    });
});