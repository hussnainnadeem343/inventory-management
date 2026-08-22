<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
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
    Route::resource('inventory', InventoryController::class)->except('show');
    Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::middleware('super_admin')->group(function () {
        Route::resource('brands', BrandController::class)->except(['index', 'show']);
        Route::resource('categories', CategoryController::class)->except(['index', 'show']);
        Route::resource('users', UserController::class)->except('show');
    });
});
