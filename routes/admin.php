<?php

use App\Http\Controllers\Admin\AccountTypeController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DepositController;
use App\Http\Controllers\Admin\KycReviewController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\PoolController;
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

    // Deposits (capital into pools)
    Route::get('/deposits', [DepositController::class, 'index'])->name('deposits.index');
    Route::post('/deposits', [DepositController::class, 'store'])->name('deposits.store');
    Route::post('/deposits/{deposit}/approve', [DepositController::class, 'approve'])->name('deposits.approve');
    Route::post('/deposits/{deposit}/reject', [DepositController::class, 'reject'])->name('deposits.reject');

    // Message center / support
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{ticket}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{ticket}/reply', [MessageController::class, 'reply'])->name('messages.reply');
    Route::patch('/messages/{ticket}/status', [MessageController::class, 'updateStatus'])->name('messages.status');

    // Pool / PnL
    Route::get('/pool', [PoolController::class, 'index'])->name('pool.index');
    Route::post('/pool', [PoolController::class, 'store'])->name('pool.store');
    Route::patch('/pool/{pool}', [PoolController::class, 'update'])->name('pool.update');
    Route::delete('/pool/{pool}', [PoolController::class, 'destroy'])->name('pool.destroy');
    Route::post('/pool/sync', [PoolController::class, 'sync'])->name('pool.sync');
});
