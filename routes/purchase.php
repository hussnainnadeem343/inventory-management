<?php

use App\Http\Controllers\Purchase\GoodsReceivedController;
use App\Http\Controllers\Purchase\PurchaseOrderController;
use App\Http\Controllers\Purchase\SupplierController;
use Illuminate\Support\Facades\Route;

// Purchase & Procurement Module Routes
Route::prefix('purchases')->name('purchases.')->group(function () {
    // Suppliers Management (Create before {supplier} parameter)
    Route::middleware('permission:purchases.create')->group(function () {
        Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    });

    Route::middleware('permission:purchases.view')->group(function () {
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    });

    Route::middleware('permission:purchases.delete')->group(function () {
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    });

    // Purchase Orders (PO) (Create before {order} parameter)
    Route::middleware('permission:purchases.create')->group(function () {
        Route::get('/orders/create', [PurchaseOrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [PurchaseOrderController::class, 'store'])->name('orders.store');
        Route::post('/orders/{order}/approve', [PurchaseOrderController::class, 'approve'])->name('orders.approve');
        Route::post('/orders/{order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('orders.cancel');
    });

    Route::middleware('permission:purchases.view')->group(function () {
        Route::get('/orders', [PurchaseOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [PurchaseOrderController::class, 'show'])->name('orders.show');
    });

    // Goods Received Notes (GRN Inward) (Create before {grn} parameter)
    Route::middleware('permission:purchases.grn')->group(function () {
        Route::get('/grn/create', [GoodsReceivedController::class, 'create'])->name('grn.create');
        Route::post('/grn', [GoodsReceivedController::class, 'store'])->name('grn.store');
    });

    Route::middleware('permission:purchases.view')->group(function () {
        Route::get('/grn', [GoodsReceivedController::class, 'index'])->name('grn.index');
        Route::get('/grn/{grn}', [GoodsReceivedController::class, 'show'])->name('grn.show');
    });
});
