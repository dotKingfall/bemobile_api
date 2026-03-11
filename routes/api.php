<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;

//PUBLIC ROUTES
Route::post('/login', [AuthController::class, 'login']);
Route::post('/buy', [TransactionController::class, 'store']);

//PROTECTED ROUTES
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});