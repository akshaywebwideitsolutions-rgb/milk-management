<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillCycleController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeductionController;
use App\Http\Controllers\Api\FarmerController;
use App\Http\Controllers\Api\FarmerHistoryController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\MilkCollectionController;
use App\Http\Controllers\Api\MilkRateController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->name('api.')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', DashboardController::class);

    Route::apiResource('farmers', FarmerController::class)->except(['destroy']);
    Route::delete('/farmers/{farmer}', [FarmerController::class, 'destroy'])->middleware(\App\Http\Middleware\RequireAdmin::class);

    Route::get('/milk-collection', [MilkCollectionController::class, 'index']);
    Route::post('/milk-collection', [MilkCollectionController::class, 'store']);
    Route::get('/milk-collection/get-rate', [MilkCollectionController::class, 'getRate']);

    Route::get('/milk-rates', [MilkRateController::class, 'index']);
    Route::put('/milk-rates', [MilkRateController::class, 'update'])->middleware(\App\Http\Middleware\RequireAdmin::class);
    Route::post('/milk-rates', [MilkRateController::class, 'update'])->middleware(\App\Http\Middleware\RequireAdmin::class);
    Route::post('/milk-rates/import', [MilkRateController::class, 'import'])->middleware(\App\Http\Middleware\RequireAdmin::class);

    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::post('/invoices/generate', [InvoiceController::class, 'generate']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::patch('/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->middleware(\App\Http\Middleware\RequireAdmin::class);
    Route::post('/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->middleware(\App\Http\Middleware\RequireAdmin::class);

    Route::get('/bill-cycles', [BillCycleController::class, 'index']);
    Route::post('/bill-cycles', [BillCycleController::class, 'store'])->middleware(\App\Http\Middleware\RequireAdmin::class);
    Route::patch('/bill-cycles/settings', [BillCycleController::class, 'updateSettings'])->middleware(\App\Http\Middleware\RequireAdmin::class);
    Route::post('/bill-cycles/settings', [BillCycleController::class, 'updateSettings'])->middleware(\App\Http\Middleware\RequireAdmin::class);

    Route::apiResource('deductions', DeductionController::class)->except(['show']);

    Route::get('/farmer-history', FarmerHistoryController::class);
    Route::get('/reports', ReportController::class);
});
