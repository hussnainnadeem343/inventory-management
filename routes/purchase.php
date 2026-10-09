<?php

use App\Http\Controllers\Purchase\GoodsReceivedController;
use App\Http\Controllers\Purchase\InwardGatePassController;
use App\Http\Controllers\Purchase\PurchaseInvoiceController;
use App\Http\Controllers\Purchase\PurchaseOrderController;
use App\Http\Controllers\Purchase\PurchaseRequisitionController;
use App\Http\Controllers\Purchase\PurchaseReturnController;
use App\Http\Controllers\Purchase\SupplierController;
use Illuminate\Support\Facades\Route;

// Complete 6-Stage Enterprise Purchase & Procurement Module Routes
Route::prefix('purchases')->name('purchases.')->group(function () {
    // 1. Suppliers Management
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

    // 2. Stage 1: Purchase Requisitions (PR)
    Route::middleware('permission:purchases.requisitions')->group(function () {
        Route::get('/requisitions/create', [PurchaseRequisitionController::class, 'create'])->name('requisitions.create');
        Route::post('/requisitions', [PurchaseRequisitionController::class, 'store'])->name('requisitions.store');
        Route::post('/requisitions/{requisition}/approve', [PurchaseRequisitionController::class, 'approve'])->name('requisitions.approve');
        Route::post('/requisitions/{requisition}/reject', [PurchaseRequisitionController::class, 'reject'])->name('requisitions.reject');
        Route::post('/requisitions/{requisition}/convert-po', [PurchaseRequisitionController::class, 'convertToPo'])->name('requisitions.convert_po');
    });

    Route::middleware('permission:purchases.view')->group(function () {
        Route::get('/requisitions', [PurchaseRequisitionController::class, 'index'])->name('requisitions.index');
        Route::get('/requisitions/{requisition}', [PurchaseRequisitionController::class, 'show'])->name('requisitions.show');
    });

    // 3. Stage 2: Purchase Orders (PO)
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

    // 4. Stage 3: Inward Gate Pass (IGP)
    Route::middleware('permission:purchases.igp')->group(function () {
        Route::get('/igp/create', [InwardGatePassController::class, 'create'])->name('igp.create');
        Route::post('/igp', [InwardGatePassController::class, 'store'])->name('igp.store');
    });

    Route::middleware('permission:purchases.view')->group(function () {
        Route::get('/igp', [InwardGatePassController::class, 'index'])->name('igp.index');
        Route::get('/igp/{igp}', [InwardGatePassController::class, 'show'])->name('igp.show');
    });

    // 5. Stage 4: Goods Received Notes (GRN Inward)
    Route::middleware('permission:purchases.grn')->group(function () {
        Route::get('/grn/create', [GoodsReceivedController::class, 'create'])->name('grn.create');
        Route::post('/grn', [GoodsReceivedController::class, 'store'])->name('grn.store');
    });

    Route::middleware('permission:purchases.view')->group(function () {
        Route::get('/grn', [GoodsReceivedController::class, 'index'])->name('grn.index');
        Route::get('/grn/{grn}', [GoodsReceivedController::class, 'show'])->name('grn.show');
    });

    // 6. Stage 5: Purchase Invoices (PI / Bills)
    Route::middleware('permission:purchases.invoices')->group(function () {
        Route::get('/invoices/create', [PurchaseInvoiceController::class, 'create'])->name('invoices.create');
        Route::post('/invoices', [PurchaseInvoiceController::class, 'store'])->name('invoices.store');
    });

    Route::middleware('permission:purchases.view')->group(function () {
        Route::get('/invoices', [PurchaseInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [PurchaseInvoiceController::class, 'show'])->name('invoices.show');
    });

    // 7. Stage 6: Purchase Returns (Debit Notes)
    Route::middleware('permission:purchases.return')->group(function () {
        Route::get('/returns/create', [PurchaseReturnController::class, 'create'])->name('returns.create');
        Route::post('/returns', [PurchaseReturnController::class, 'store'])->name('returns.store');
    });

    Route::middleware('permission:purchases.view')->group(function () {
        Route::get('/returns', [PurchaseReturnController::class, 'index'])->name('returns.index');
        Route::get('/returns/{return}', [PurchaseReturnController::class, 'show'])->name('returns.show');
    });
});
