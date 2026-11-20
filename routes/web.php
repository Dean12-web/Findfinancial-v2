<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\Invoice\FormatTransactionController;
use App\Http\Controllers\Invoice\InvoiceController;
use App\Http\Controllers\Invoice\InvoiceLookupController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PurchaseController;
use App\Http\Middleware\EnsureUserHasInvoiceAccess;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Penjualan
    Route::middleware(EnsureUserHasInvoiceAccess::class)->group(function () {
        Route::get('/invoice', [InvoiceController::class, 'index'])->name('invoice');

        Route::get('/invoice/new', [InvoiceController::class, 'create'])->name('invoice.new');
        Route::post('/invoice', [InvoiceController::class, 'store'])->name('invoice.store');

        Route::get('/invoice/lookup/buyers', [InvoiceLookupController::class, 'buyers'])->name('invoice.lookup.buyers');
        Route::get('/invoice/lookup/products', [InvoiceLookupController::class, 'products'])->name('invoice.lookup.products');

        Route::get('/invoice/{order_id}', [InvoiceController::class, 'show'])->name('invoice.show');
        Route::get('/invoice/{order_id}/print', [InvoiceController::class, 'print'])->name('invoice.print');

        Route::post('/format-transactions', [FormatTransactionController::class, 'store'])->name('format-transactions.store');
        Route::delete('/format-transactions/{formatTransaction}', [FormatTransactionController::class, 'destroy'])->name('format-transactions.destroy');

        Route::get('/pos', [PosController::class, 'index'])->name('pos');
        Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
        Route::get('/pos/print-thermal', [PosController::class, 'printThermal'])->name('pos.print');
    });



    Route::get('/purchase', [PurchaseController::class, 'index'])->name('purchase');
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses');
});