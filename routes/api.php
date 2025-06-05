<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\TransferController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'api.key'])->group(function () {
    Route::post('/accounts', [BankAccountController::class, 'store']);
    Route::get('/accounts/{id}/balance', [BankAccountController::class, 'balance']);
    Route::get('/accounts/{id}/history', [BankAccountController::class, 'history']);
    Route::post('/transfer', [TransferController::class, 'store']);
});
