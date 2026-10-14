<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::view('/dashboard', 'dashboard.index')->name('dashboard');

Route::view('/farmers', 'farmers.index')->name('farmers.index');
Route::view('/farmers/create', 'farmers.create')->name('farmers.create');
Route::view('/farmers/{farmer}/edit', 'farmers.edit')->name('farmers.edit');

Route::view('/milk-collection', 'milk-collection.index')->name('milk-collection.index');
Route::get('/milk-collection/get-rate', fn () => response()->json([
    'message' => 'Use the API endpoint: /api/milk-collection/get-rate',
], 308))->name('milk-collection.get-rate');

Route::view('/milk-rates', 'milk-rates.index')->name('milk-rates.index');
Route::view('/invoices', 'invoices.index')->name('invoices.index');
Route::view('/invoices/print-all', 'invoices.print-all')->name('invoices.print-all');
Route::view('/invoices/{invoice}/print', 'invoices.print')->name('invoices.print');
Route::view('/invoices/{invoice}', 'invoices.show')->name('invoices.show');

Route::view('/bill-cycles', 'bill-cycles.index')->name('bill-cycles.index');
Route::view('/deductions', 'deductions.index')->name('deductions.index');
Route::view('/farmer-history', 'farmer-history.index')->name('farmer-history.index');
Route::view('/reports', 'reports.index')->name('reports.index');
Route::view('/settings', 'settings.index')->name('settings.index');
