<?php

use App\Http\Controllers\Lube\DrumDecantingController;
use App\Http\Controllers\Lube\LubeAnalyticsController;
use App\Http\Controllers\Lube\LubeCommissionController;
use App\Http\Controllers\Lube\LubeHubController;
use App\Http\Controllers\Lube\LubeLaborServiceController;
use App\Http\Controllers\Lube\LubePackageController;
use App\Http\Controllers\Lube\LubePosController;
use App\Http\Controllers\Lube\LubeReminderController;
use App\Http\Controllers\Lube\PitTechnicianController;
use App\Http\Controllers\Lube\VehicleHistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('lube')->name('lube.')->group(function () {

    // Auto Lube Station Module Hub (Launchpad)
    Route::get('/hub', LubeHubController::class)->name('hub');

    // 1. Station Analytics
    Route::middleware('permission:lube.analytics')->group(function () {
        Route::get('/analytics', [LubeAnalyticsController::class, 'index'])->name('analytics');
    });

    // 2. Lube Counter POS
    Route::middleware('permission:lube.pos')->group(function () {
        Route::get('/pos', [LubePosController::class, 'index'])->name('pos');
        Route::get('/pos/search', [LubePosController::class, 'search'])->name('pos.search');
        Route::post('/pos', [LubePosController::class, 'store'])->name('pos.store');
        Route::get('/pos/receipt/{sale}', [LubePosController::class, 'receipt'])->name('pos.receipt');
        Route::get('/pos/sticker/{serviceRecord}', [LubePosController::class, 'sticker'])->name('pos.sticker');
    });

    // 3. Labor & Pit Services
    Route::get('/services', [LubeLaborServiceController::class, 'index'])->name('services.index');
    Route::post('/services', [LubeLaborServiceController::class, 'store'])->name('services.store');
    Route::put('/services/{service}', [LubeLaborServiceController::class, 'update'])->name('services.update');
    Route::delete('/services/{service}', [LubeLaborServiceController::class, 'destroy'])->name('services.destroy');

    // 4. Service Packages
    Route::middleware('permission:lube.packages')->group(function () {
        Route::get('/packages', [LubePackageController::class, 'index'])->name('packages.index');
        Route::post('/packages', [LubePackageController::class, 'store'])->name('packages.store');
        Route::put('/packages/{package}', [LubePackageController::class, 'update'])->name('packages.update');
        Route::delete('/packages/{package}', [LubePackageController::class, 'destroy'])->name('packages.destroy');
    });

    // 4. Vehicle History
    Route::middleware('permission:lube.vehicles')->group(function () {
        Route::get('/vehicles', [VehicleHistoryController::class, 'index'])->name('vehicles.index');
        Route::get('/vehicles/{vehicle}', [VehicleHistoryController::class, 'show'])->name('vehicles.show');
    });

    // 5. Oil Due Reminders
    Route::middleware('permission:lube.reminders')->group(function () {
        Route::get('/reminders', [LubeReminderController::class, 'index'])->name('reminders.index');
        Route::post('/reminders/{serviceRecord}/mark-contacted', [LubeReminderController::class, 'markContacted'])->name('reminders.contacted');
    });

    // 6. Pit Technicians
    Route::middleware('permission:lube.technicians')->group(function () {
        Route::get('/technicians', [PitTechnicianController::class, 'index'])->name('technicians.index');
        Route::post('/technicians', [PitTechnicianController::class, 'store'])->name('technicians.store');
        Route::put('/technicians/{technician}', [PitTechnicianController::class, 'update'])->name('technicians.update');
        Route::delete('/technicians/{technician}', [PitTechnicianController::class, 'destroy'])->name('technicians.destroy');
    });

    // 7. Mechanic Commissions
    Route::middleware('permission:lube.commissions')->group(function () {
        Route::get('/commissions', [LubeCommissionController::class, 'index'])->name('commissions.index');
        Route::post('/commissions/{serviceRecord}/pay', [LubeCommissionController::class, 'markPaid'])->name('commissions.pay');
        Route::post('/commissions/bulk-pay', [LubeCommissionController::class, 'bulkPay'])->name('commissions.bulk_pay');
    });

    // 8. Drum Decanting
    Route::middleware('permission:lube.decanting')->group(function () {
        Route::get('/decanting', [DrumDecantingController::class, 'index'])->name('decanting.index');
        Route::post('/decanting', [DrumDecantingController::class, 'store'])->name('decanting.store');
    });
});
