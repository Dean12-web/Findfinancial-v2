<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\Invoice\InvoiceController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PurchaseController;
use App\Http\Middleware\EnsureUserHasInvoiceAccess;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Penjualan
    Route::get('/invoice', [InvoiceController::class, 'index'])->middleware(EnsureUserHasInvoiceAccess::class)->name('invoice');
    Route::get('/invoice/{order_id}', [InvoiceController::class, 'show'])->name('invoice.show');


    Route::get('/pos', [PosController::class, 'index'])->name('pos');
    Route::get('/purchase', [PurchaseController::class, 'index'])->name('purchase');
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses');
});