<?php

use App\Http\Controllers\Finance\AccountController;
use App\Http\Controllers\Finance\ExpenseController;
use App\Http\Controllers\Finance\FinancialReportController;
use App\Http\Controllers\Finance\JournalEntryController;
use App\Http\Controllers\Finance\PaymentController;
use Illuminate\Support\Facades\Route;

// Finance & Accounts Module Routes
Route::prefix('finance')->name('finance.')->group(function () {
    // Chart of Accounts & Ledgers
    Route::middleware('permission:accounts.view')->group(function () {
        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::get('/accounts/{account}/ledger', [AccountController::class, 'ledger'])->name('accounts.ledger');
    });

    Route::middleware('permission:accounts.manage')->group(function () {
        Route::get('/accounts/create', [AccountController::class, 'create'])->name('accounts.create');
        Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
    });

    // Daily Expenses
    Route::middleware('permission:accounts.view')->group(function () {
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    });
    Route::middleware('permission:accounts.vouchers')->group(function () {
        Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    });

    // Payments & Receipts
    Route::middleware('permission:accounts.view')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    });
    Route::middleware('permission:accounts.vouchers')->group(function () {
        Route::get('/payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    });

    // General Journal Vouchers
    Route::middleware('permission:accounts.vouchers')->group(function () {
        Route::get('/journal/create', [JournalEntryController::class, 'create'])->name('journal.create');
        Route::post('/journal', [JournalEntryController::class, 'store'])->name('journal.store');
    });
    Route::middleware('permission:accounts.view')->group(function () {
        Route::get('/journal', [JournalEntryController::class, 'index'])->name('journal.index');
        Route::get('/journal/{journal}', [JournalEntryController::class, 'show'])->name('journal.show');
    });

    // Financial Reports
    Route::middleware('permission:accounts.reports')->group(function () {
        Route::get('/reports/profit-loss', [FinancialReportController::class, 'profitLoss'])->name('reports.profit-loss');
        Route::get('/reports/balance-sheet', [FinancialReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
        Route::get('/reports/trial-balance', [FinancialReportController::class, 'trialBalance'])->name('reports.trial-balance');
    });
});
