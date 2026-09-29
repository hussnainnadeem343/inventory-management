<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
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

    // Products Module
    Route::middleware('permission:products.import')->group(function () {
        Route::get('/products/template', [\App\Http\Controllers\ProductImportController::class, 'sampleTemplate'])->name('products.template');
        Route::post('/products/import', [\App\Http\Controllers\ProductImportController::class, 'import'])->name('products.import');
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

    // Catalog (Brands & Categories)
    Route::middleware('permission:catalog.manage')->group(function () {
        Route::resource('brands', BrandController::class)->except('show');
        Route::resource('categories', CategoryController::class)->except('show');
    });

    // Reports
    Route::middleware('permission:reports.sales')->group(function () {
        Route::get('/reports/sales', [\App\Http\Controllers\ReportController::class, 'sales'])->name('reports.sales');
    });
    Route::middleware('permission:reports.stock_movement')->group(function () {
        Route::get('/reports/stock', [\App\Http\Controllers\ReportController::class, 'stockMovement'])->name('reports.stock');
    });
    Route::middleware('permission:reports.expiry')->group(function () {
        Route::get('/reports/expiry', [\App\Http\Controllers\ReportController::class, 'expiry'])->name('reports.expiry');
    });

    // Users & Roles Management
    Route::middleware('permission:users.view,users.manage')->group(function () {
        Route::resource('users', UserController::class)->except('show');
    });
    Route::middleware('permission:roles.manage')->group(function () {
        Route::resource('roles', \App\Http\Controllers\RoleController::class)->except('show');
    });

    Route::middleware('super_admin')->group(function () {
        Route::resource('shops', \App\Http\Controllers\ShopController::class)->except('show');
    });
});
