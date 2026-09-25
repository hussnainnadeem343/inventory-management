<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('products', ProductController::class)->except('show');
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/export', [StockController::class, 'export'])->name('stock.export');
    Route::get('/stock/{product}/add', [StockController::class, 'addForm'])->name('stock.add-form');
    Route::post('/stock/{product}/add', [StockController::class, 'add'])->name('stock.add');
    Route::get('/stock/{product}/sell', [StockController::class, 'sellForm'])->name('stock.sell-form');
    Route::post('/stock/{product}/sell', [StockController::class, 'sell'])->name('stock.sell');
    Route::get('/stock/{product}/history', [StockController::class, 'history'])->name('stock.history');
    Route::post('/stock/{product}/return', [StockController::class, 'customerReturn'])->name('stock.return');
    Route::post('/stock/{product}/damage', [StockController::class, 'damageLoss'])->name('stock.damage');
    Route::post('/stock/{product}/exchange', [StockController::class, 'exchange'])->name('stock.exchange');
    Route::get('/inventory/export', [InventoryController::class, 'export'])->name('inventory.export');
    Route::post('/inventory/{inventory}/sell', [InventoryController::class, 'sell'])->name('inventory.sell');
    Route::post('/inventory/{inventory}/add-stock', [InventoryController::class, 'addStock'])->name('inventory.add-stock');
    Route::resource('inventory', InventoryController::class)->except('show');
    Route::resource('brands', BrandController::class)->except('show');
    Route::resource('categories', CategoryController::class)->except('show');

    Route::get('/reports/sales', [\App\Http\Controllers\ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/stock', [\App\Http\Controllers\ReportController::class, 'stockMovement'])->name('reports.stock');
    Route::get('/reports/expiry', [\App\Http\Controllers\ReportController::class, 'expiry'])->name('reports.expiry');

    Route::middleware('shop_admin')->group(function () {
        Route::resource('users', UserController::class)->except('show');
    });

    Route::middleware('super_admin')->group(function () {
        Route::resource('shops', \App\Http\Controllers\ShopController::class)->except('show');
    });
});
