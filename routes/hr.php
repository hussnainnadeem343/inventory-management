<?php

use App\Http\Controllers\HR\AttendanceController;
use App\Http\Controllers\HR\DepartmentController;
use App\Http\Controllers\HR\EmployeeController;
use App\Http\Controllers\HR\PayrollController;
use Illuminate\Support\Facades\Route;

// HR & Payroll Module Routes
Route::prefix('hr')->name('hr.')->group(function () {
    // Departments & Designations
    Route::middleware('permission:hr.employees')->group(function () {
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'storeDepartment'])->name('departments.store');
        Route::post('/designations', [DepartmentController::class, 'storeDesignation'])->name('designations.store');

        // Employees Directory & Profiles
        Route::resource('employees', EmployeeController::class);
    });

    // Daily Attendance
    Route::middleware('permission:hr.attendance')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    });

    // Monthly Payroll
    Route::middleware('permission:hr.payroll')->group(function () {
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/create', [PayrollController::class, 'create'])->name('payroll.create');
        Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
        Route::post('/payroll/{payroll}/pay', [PayrollController::class, 'pay'])->name('payroll.pay');
        Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
    });
});
