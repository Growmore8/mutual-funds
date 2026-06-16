<?php

use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

// Post-login landing: admins -> admin, clients -> client dashboard (gated by onboarding).
Route::get('/dashboard', function () {
    return Auth::user()?->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('client.dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Email OTP verification
    Route::get('/verify-otp', [OtpController::class, 'show'])->name('otp.show');
    Route::post('/verify-otp', [OtpController::class, 'verify'])->name('otp.verify');
    Route::post('/verify-otp/resend', [OtpController::class, 'resend'])->name('otp.resend');

    // KYC upload + status (requires OTP verified first)
    Route::get('/kyc', [KycController::class, 'show'])->name('kyc.show');
    Route::post('/kyc', [KycController::class, 'store'])->name('kyc.store');

    // Client dashboard (only after OTP + KYC approval)
    Route::get('/app', [\App\Http\Controllers\ClientDashboardController::class, 'index'])
        ->middleware('onboarded')->name('client.dashboard');

    // Accounts (1st free; additional accounts need admin approval)
    Route::get('/accounts', [\App\Http\Controllers\AccountRequestController::class, 'index'])->name('accounts.index');
    Route::post('/accounts/request', [\App\Http\Controllers\AccountRequestController::class, 'store'])->name('accounts.request');

    // Withdrawals (profit only; request -> admin approval)
    Route::get('/withdraw', [\App\Http\Controllers\WithdrawalController::class, 'create'])->name('withdraw.create');
    Route::post('/withdraw', [\App\Http\Controllers\WithdrawalController::class, 'store'])->name('withdraw.store');

    // Statements
    Route::get('/transactions', [\App\Http\Controllers\StatementController::class, 'transactions'])->name('client.transactions');
    Route::get('/profit', [\App\Http\Controllers\StatementController::class, 'profit'])->name('client.profit');

    // Support tickets / message center
    Route::get('/support', [\App\Http\Controllers\SupportController::class, 'index'])->name('support.index');
    Route::get('/support/new', [\App\Http\Controllers\SupportController::class, 'create'])->name('support.create');
    Route::post('/support', [\App\Http\Controllers\SupportController::class, 'store'])->name('support.store');
    Route::get('/support/{ticket}', [\App\Http\Controllers\SupportController::class, 'show'])->name('support.show');
    Route::post('/support/{ticket}/reply', [\App\Http\Controllers\SupportController::class, 'reply'])->name('support.reply');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
