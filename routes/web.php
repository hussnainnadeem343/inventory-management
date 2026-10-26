<?php

use App\Http\Controllers\AuthController;
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
    Route::get('/reports', \App\Http\Controllers\ReportsHubController::class)->name('reports.index');

    // Reports
    Route::middleware('permission:reports.sales')->group(function () {
        Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    });
    Route::middleware('permission:reports.stock_movement')->group(function () {
        Route::get('/reports/stock', [ReportController::class, 'stockMovement'])->name('reports.stock');
    });
    Route::middleware('permission:reports.expiry')->group(function () {
        Route::get('/reports/expiry', [ReportController::class, 'expiry'])->name('reports.expiry');
    });

    // Management Module Hub (Launchpad)
    Route::get('/management', \App\Http\Controllers\ManagementHubController::class)->name('management.index');

    // Users & Roles Management
    Route::middleware('permission:users.view,users.manage')->group(function () {
        Route::resource('users', UserController::class)->except('show');
    });
    Route::middleware('permission:roles.manage')->group(function () {
        Route::resource('roles', RoleController::class)->except('show');
    });

    // Super Admin: Shop Management
    Route::middleware('super_admin')->group(function () {
        Route::resource('shops', ShopController::class)->except('show');
    });
});
