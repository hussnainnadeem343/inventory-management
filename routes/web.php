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
    Route::get('/inventory/export', [InventoryController::class, 'export'])->name('inventory.export');
    Route::post('/inventory/{inventory}/sell', [InventoryController::class, 'sell'])->name('inventory.sell');
    Route::post('/inventory/{inventory}/add-stock', [InventoryController::class, 'addStock'])->name('inventory.add-stock');
    Route::resource('inventory', InventoryController::class)->except('show');
    Route::resource('brands', BrandController::class)->except(['show', 'destroy']);
    Route::resource('categories', CategoryController::class)->except(['show', 'destroy']);
    Route::middleware('super_admin')->group(function () {
        Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::resource('users', UserController::class)->except('show');
    });
});
