<?php

use App\Http\Controllers\Inventory\BrandController;
use App\Http\Controllers\Inventory\CategoryController;
use App\Http\Controllers\Inventory\InventoryController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\ProductImportController;
use App\Http\Controllers\Inventory\StockController;
use Illuminate\Support\Facades\Route;

// Products Module
Route::middleware('permission:products.import')->group(function () {
    Route::get('/products/template', [ProductImportController::class, 'sampleTemplate'])->name('products.template');
    Route::post('/products/import', [ProductImportController::class, 'import'])->name('products.import');
});
Route::middleware('permission:products.view')->group(function () {
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
});
Route::middleware('permission:products.create')->group(function () {
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
});
Route::middleware('permission:products.edit')->group(function () {
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.patch-update');
});
Route::middleware('permission:products.delete')->group(function () {
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
});

// Stock & Inventory Operations
Route::middleware('permission:stock.view')->group(function () {
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::resource('inventory', InventoryController::class)->except(['show', 'export', 'sell', 'addStock']);
});
Route::middleware('permission:stock.export')->group(function () {
    Route::get('/stock/export', [StockController::class, 'export'])->name('stock.export');
    Route::get('/inventory/export', [InventoryController::class, 'export'])->name('inventory.export');
});
Route::middleware('permission:stock.add')->group(function () {
    Route::get('/stock/{product}/add', [StockController::class, 'addForm'])->name('stock.add-form');
    Route::post('/stock/{product}/add', [StockController::class, 'add'])->name('stock.add');
    Route::post('/inventory/{inventory}/add-stock', [InventoryController::class, 'addStock'])->name('inventory.add-stock');
});
Route::middleware('permission:stock.sell')->group(function () {
    Route::get('/stock/{product}/sell', [StockController::class, 'sellForm'])->name('stock.sell-form');
    Route::post('/stock/{product}/sell', [StockController::class, 'sell'])->name('stock.sell');
    Route::post('/inventory/{inventory}/sell', [InventoryController::class, 'sell'])->name('inventory.sell');
});
Route::middleware('permission:stock.history')->group(function () {
    Route::get('/stock/{product}/history', [StockController::class, 'history'])->name('stock.history');
});
Route::middleware('permission:stock.return')->group(function () {
    Route::post('/stock/{product}/return', [StockController::class, 'customerReturn'])->name('stock.return');
});
Route::middleware('permission:stock.damage')->group(function () {
    Route::post('/stock/{product}/damage', [StockController::class, 'damageLoss'])->name('stock.damage');
});
Route::middleware('permission:stock.exchange')->group(function () {
    Route::post('/stock/{product}/exchange', [StockController::class, 'exchange'])->name('stock.exchange');
});

// Catalog (Brands, Categories & Units of Measure)
Route::middleware('permission:catalog.manage')->group(function () {
    Route::resource('brands', BrandController::class)->except('show');
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('units', \App\Http\Controllers\Inventory\UnitController::class)->except('show');
});

// Multi-Warehouse / Godown Management
Route::middleware('permission:stock.view')->group(function () {
    Route::resource('warehouses', \App\Http\Controllers\Inventory\WarehouseController::class)->except('show');
    Route::get('/transfers', [\App\Http\Controllers\Inventory\StockTransferController::class, 'index'])->name('transfers.index');
    Route::get('/transfers/create', [\App\Http\Controllers\Inventory\StockTransferController::class, 'create'])->name('transfers.create');
    Route::post('/transfers', [\App\Http\Controllers\Inventory\StockTransferController::class, 'store'])->name('transfers.store');
    Route::get('/transfers/{transfer}', [\App\Http\Controllers\Inventory\StockTransferController::class, 'show'])->name('transfers.show');

    Route::get('/adjustments', [\App\Http\Controllers\Inventory\StockAdjustmentController::class, 'index'])->name('adjustments.index');
    Route::get('/adjustments/create', [\App\Http\Controllers\Inventory\StockAdjustmentController::class, 'create'])->name('adjustments.create');
    Route::post('/adjustments', [\App\Http\Controllers\Inventory\StockAdjustmentController::class, 'store'])->name('adjustments.store');
    Route::get('/adjustments/{adjustment}', [\App\Http\Controllers\Inventory\StockAdjustmentController::class, 'show'])->name('adjustments.show');
});

