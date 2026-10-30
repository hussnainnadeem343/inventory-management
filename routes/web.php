<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public SaaS Landing Page
Route::get('/', [LandingPageController::class, 'index'])->name('landing');
Route::post('/demo-request', [LandingPageController::class, 'storeInquiry'])->name('demo.request');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Modular Sub-Domain Routes
    require __DIR__ . '/sales.php';
    require __DIR__ . '/inventory.php';
    require __DIR__ . '/purchase.php';
    require __DIR__ . '/finance.php';
    require __DIR__ . '/hr.php';
    require __DIR__ . '/lube.php';

    // Reports Module Hub (Launchpad)
    Route::get('/reports', \App\Http\Controllers\ReportsHubController::class)->name('reports.index')->middleware(['module:reports', 'permission:reports.sales,reports.stock_movement,reports.expiry,accounts.reports']);

    // Reports - Separate Screens & Excel Exports
    Route::middleware('permission:reports.sales')->group(function () {
        Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('/reports/sales/export', [ReportController::class, 'exportSales'])->name('reports.sales.export');

        Route::get('/reports/product-sales', [ReportController::class, 'productSales'])->name('reports.product-sales');
        Route::get('/reports/product-sales/export', [ReportController::class, 'exportProductSales'])->name('reports.product-sales.export');

        Route::get('/reports/cash-register', [ReportController::class, 'cashRegister'])->name('reports.cash-register');
        Route::get('/reports/cash-register/export', [ReportController::class, 'exportCashRegister'])->name('reports.cash-register.export');
        Route::get('/reports/cash-register/summary', [ReportController::class, 'cashRegisterSummary'])->name('reports.cash-register.summary');
        Route::post('/reports/cash-register/close', [ReportController::class, 'closeCashRegister'])->name('reports.cash-register.close');
        Route::get('/reports/cash-register/closings/{closing}/slip', [ReportController::class, 'cashClosingSlip'])->name('reports.cash-register.slip');

        Route::get('/reports/lube-services', [ReportController::class, 'lubeServices'])->name('reports.lube-services');
        Route::get('/reports/lube-services/export', [ReportController::class, 'exportLubeServices'])->name('reports.lube-services.export');
    });

    Route::middleware('permission:reports.sales,accounts.view')->group(function () {
        Route::get('/reports/expenses', [ReportController::class, 'expenses'])->name('reports.expenses');
        Route::get('/reports/expenses/export', [ReportController::class, 'exportExpenses'])->name('reports.expenses.export');
    });

    Route::middleware('permission:reports.stock_movement')->group(function () {
        Route::get('/reports/stock', [ReportController::class, 'stockMovement'])->name('reports.stock');
        Route::get('/reports/stock/export', [ReportController::class, 'exportStockMovement'])->name('reports.stock.export');
    });

    Route::middleware('permission:reports.expiry')->group(function () {
        Route::get('/reports/expiry', [ReportController::class, 'expiry'])->name('reports.expiry');
        Route::get('/reports/expiry/export', [ReportController::class, 'exportExpiry'])->name('reports.expiry.export');
    });

    // Management Module Hub (Launchpad)
    Route::get('/management', \App\Http\Controllers\ManagementHubController::class)->name('management.index')->middleware('permission:users.view,users.manage,roles.manage,settings.view,branches.view');

    // Users & Roles Management
    Route::middleware('permission:users.view,users.manage')->group(function () {
        Route::resource('users', UserController::class)->except('show');
    });
    Route::middleware('permission:roles.manage')->group(function () {
        Route::resource('roles', RoleController::class)->except('show');
    });

    // Multi-Branch Management (Business Owner & Super Admin)
    Route::resource('branches', BranchController::class)->except('show');

    // Super Admin: Shop Management
    Route::middleware('super_admin')->group(function () {
        Route::resource('shops', ShopController::class)->except('show');
    });
});
