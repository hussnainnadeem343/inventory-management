<?php

use App\Http\Controllers\Sales\SaleController;
use Illuminate\Support\Facades\Route;

// POS & Multi-Item Sales
Route::middleware('permission:pos.access')->group(function () {
    Route::get('/pos', [SaleController::class, 'create'])->name('pos');
    Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
});

// Invoices History & Receipts
Route::middleware('permission:sales.view_invoices')->group(function () {
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::get('/sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
});
