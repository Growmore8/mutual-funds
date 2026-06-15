<?php

use App\Http\Controllers\Admin\AccountTypeController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\KycReviewController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\TransactionController;
use App\Models\PoolAccount;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', function () {
        return view('admin.dashboard', [
            'clients' => User::where('role', 'client')->count(),
            'pendingKyc' => User::where('kyc_status', 'submitted')->count(),
            'pool' => PoolAccount::first(),
        ]);
    })->name('dashboard');

    // Clients
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::patch('/clients/{client}/status', [ClientController::class, 'updateStatus'])->name('clients.status');

    // KYC review
    Route::get('/kyc', [KycReviewController::class, 'index'])->name('kyc.index');
    Route::get('/kyc/{document}/file', [KycReviewController::class, 'file'])->name('kyc.file');
    Route::post('/kyc/{document}/approve', [KycReviewController::class, 'approve'])->name('kyc.approve');
    Route::post('/kyc/{document}/reject', [KycReviewController::class, 'reject'])->name('kyc.reject');

    // CRUD
    Route::resource('account-types', AccountTypeController::class)->except('show');
    Route::resource('payment-methods', PaymentMethodController::class)->except('show');

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
});
