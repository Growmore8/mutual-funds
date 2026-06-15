<?php

use App\Models\KycDocument;
use App\Models\PoolAccount;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
 | Admin area — full CRUD (clients, transactions, account types, payment
 | methods, KYC review) is built out in Phase 3. This is the dashboard shell.
 */
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return view('admin.dashboard', [
            'clients' => User::where('role', 'client')->count(),
            'pendingKyc' => User::where('kyc_status', 'submitted')->count(),
            'pool' => PoolAccount::first(),
        ]);
    })->name('dashboard');
});
