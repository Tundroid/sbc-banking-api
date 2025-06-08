<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\TransferController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'api.key'])->group(function () {
    
    // Routes without 'validate.identifier'
    Route::get('/accounts', [BankAccountController::class, 'index']);
    Route::post('/accounts', [BankAccountController::class, 'store']);

    // Group routes that need 'validate.identifier'
    Route::middleware('validate.identifier')->group(function () {
        Route::post('/transfers', [TransferController::class, 'store']);
        Route::get('/accounts/{identifier}', [BankAccountController::class, 'show']);
        Route::get('/accounts/{identifier}/balance', [BankAccountController::class, 'balance']);
        Route::get('/accounts/{identifier}/transfers', [TransferController::class, 'history']);
    });
});
