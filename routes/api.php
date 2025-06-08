<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\TransferController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'api.key'])->group(function () {
    Route::get('/accounts', [BankAccountController::class, 'index']);
    Route::get('/accounts/{id}', [BankAccountController::class, 'showById'])->where('id', '[0-9]+');
    Route::get('/accounts/{account_number}', [BankAccountController::class, 'showByAccountNumber']);
    Route::post('/accounts', [BankAccountController::class, 'store']);
    Route::get('/accounts/{id}/balance', [BankAccountController::class, 'balanceById'])->where('id', '[0-9]+');
    Route::get('/accounts/{account_number}/balance', [BankAccountController::class, 'balanceByAccountNumber']);
    Route::get('/accounts/{accountId}/transfers', [TransferController::class, 'history']);
    Route::post('/transfers', [TransferController::class, 'store'])->middleware('validate.identifier');
    // Route::post('/transfers/acc_number', [TransferController::class, 'storeByAccountNumber']);
});


// Route::middleware(['auth:sanctum', 'api.key'])->group(function () {
//     Route::post('/transfers', [TransferController::class, 'store']);
//     Route::get('/accounts/{accountId}/transfers', [TransferController::class, 'history']);
// });



// Route::middleware(['auth:sanctum', 'api.key'])->group(function () {
//     Route::get('/accounts', [BankAccountController::class, 'index']);
//     Route::post('/accounts', [BankAccountController::class, 'store']);
//     Route::get('/accounts/{id}', [BankAccountController::class, 'show']);
// });