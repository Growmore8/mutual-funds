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

// Public, read-only feed of active account types (consumed by the marketing site).
Route::get('/api/account-types', function () {
    $types = \Illuminate\Support\Facades\Cache::remember('public.account-types', 300, function () {
        return \App\Models\AccountType::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['name', 'slug', 'min_deposit', 'max_deposit', 'profit_share_pct', 'lock_in_months', 'description', 'features'])
            ->map(fn ($t) => [
                'name' => $t->name,
                'slug' => $t->slug,
                'min_deposit' => (float) $t->min_deposit,
                'max_deposit' => $t->max_deposit !== null ? (float) $t->max_deposit : null,
                'profit_share_pct' => (float) $t->profit_share_pct,
                'lock_in_months' => (int) $t->lock_in_months,
                'description' => $t->description,
                'features' => $t->features ?? [],
            ])
            ->values();
    });

    return response()->json(['data' => $types])
        ->header('Access-Control-Allow-Origin', '*');
})->name('api.account-types');

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

    // App-lock screen + unlock (must stay OUTSIDE the 'locked' group)
    Route::get('/lock', [\App\Http\Controllers\SecurityController::class, 'showLock'])->name('lock.show');
    Route::post('/lock', [\App\Http\Controllers\SecurityController::class, 'unlock'])->name('lock.unlock');
    Route::post('/lock/now', [\App\Http\Controllers\SecurityController::class, 'lockNow'])->name('lock.now');

    // Security settings (PIN + biometric)
    Route::get('/security', [\App\Http\Controllers\SecurityController::class, 'index'])->name('security.index');
    Route::post('/security/pin', [\App\Http\Controllers\SecurityController::class, 'setPin'])->name('security.pin.set');
    Route::delete('/security/pin', [\App\Http\Controllers\SecurityController::class, 'removePin'])->name('security.pin.remove');

    // WebAuthn biometric (passkeys) — registration + app-unlock
    Route::post('/webauthn/register/options', [\App\Http\Controllers\WebAuthnController::class, 'registerOptions'])->name('webauthn.register.options');
    Route::post('/webauthn/register', [\App\Http\Controllers\WebAuthnController::class, 'register'])->name('webauthn.register');
    Route::post('/webauthn/unlock/options', [\App\Http\Controllers\WebAuthnController::class, 'unlockOptions'])->name('webauthn.unlock.options');
    Route::post('/webauthn/unlock', [\App\Http\Controllers\WebAuthnController::class, 'unlock'])->name('webauthn.unlock');
    Route::delete('/webauthn', [\App\Http\Controllers\WebAuthnController::class, 'destroy'])->name('webauthn.destroy');

    // Client app — gated by the app-lock (PIN/biometric) when configured.
    Route::middleware('locked')->group(function () {
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
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
