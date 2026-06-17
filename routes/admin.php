<?php

use App\Http\Controllers\Admin\AccountRequestController;
use App\Http\Controllers\Admin\AccountTypeController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DepositController;
use App\Http\Controllers\Admin\KycReviewController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\PoolController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Models\PoolAccount;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', function () {
        $pendingKyc = \App\Models\KycDocument::where('status', 'submitted')->count();
        $pendingDeposits = \App\Models\Deposit::where('status', 'pending')->count();
        $pendingWithdrawals = \App\Models\Withdrawal::where('status', 'pending')->count();
        $pendingAccountRequests = \App\Models\AccountRequest::where('status', 'pending')->count();

        // Pool-account-wise cumulative PnL growth over the last 14 days.
        $pools = PoolAccount::orderBy('account_ref')->get();
        $days = collect(range(0, 13))->map(fn ($i) => now()->subDays(13 - $i)->toDateString());
        $snaps = \App\Models\PoolSnapshot::where('snapshot_date', '>=', $days->first())
            ->get(['pool_account_id', 'snapshot_date', 'pnl']);

        $series = $pools->map(function ($p) use ($snaps, $days) {
            $cum = 0.0;
            $points = $days->map(function ($d) use ($snaps, $p, &$cum) {
                $cum += (float) $snaps->where('pool_account_id', $p->id)
                    ->filter(fn ($s) => $s->snapshot_date->toDateString() === $d)->sum('pnl');

                return round($cum, 2);
            })->values();

            return ['id' => $p->id, 'ref' => $p->account_ref, 'name' => $p->name, 'points' => $points];
        })->values();

        return view('admin.dashboard', [
            'clients' => User::where('role', 'client')->count(),
            'totalDeposits' => (float) \App\Models\Deposit::where('status', 'approved')->sum('amount'),
            'totalWithdrawals' => (float) \App\Models\Withdrawal::where('status', 'approved')->sum('amount'),
            'poolCount' => $pools->count(),
            'pendingRequests' => $pendingKyc + $pendingDeposits + $pendingWithdrawals + $pendingAccountRequests,
            'pendingKyc' => $pendingKyc,
            'pendingDeposits' => $pendingDeposits,
            'pendingWithdrawals' => $pendingWithdrawals,
            'pendingAccountRequests' => $pendingAccountRequests,
            'chartLabels' => $days->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('d M')),
            'chartSeries' => $series,
        ]);
    })->name('dashboard');

    // Clients
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::get('/clients/{client}/statement', [ClientController::class, 'statement'])->name('clients.statement');
    Route::patch('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::patch('/clients/{client}/status', [ClientController::class, 'updateStatus'])->name('clients.status');
    Route::post('/clients/{client}/kyc', [ClientController::class, 'uploadKyc'])->name('clients.kyc.upload');
    Route::post('/clients/{client}/kyc/decision', [ClientController::class, 'kycDecision'])->name('clients.kyc.decision');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

    // Additional-account requests
    Route::get('/account-requests', [AccountRequestController::class, 'index'])->name('account-requests.index');
    Route::post('/account-requests/{accountRequest}/approve', [AccountRequestController::class, 'approve'])->name('account-requests.approve');
    Route::post('/account-requests/{accountRequest}/reject', [AccountRequestController::class, 'reject'])->name('account-requests.reject');

    // KYC review
    Route::get('/kyc', [KycReviewController::class, 'index'])->name('kyc.index');
    Route::get('/kyc/{document}/file/{side?}', [KycReviewController::class, 'file'])->name('kyc.file');
    Route::post('/kyc/{document}/approve', [KycReviewController::class, 'approve'])->name('kyc.approve');
    Route::post('/kyc/{document}/reject', [KycReviewController::class, 'reject'])->name('kyc.reject');

    // CRUD
    Route::resource('account-types', AccountTypeController::class)->except('show');
    Route::resource('payment-methods', PaymentMethodController::class)->except('show');

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::patch('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

    // Deposits (capital into pools)
    Route::get('/deposits', [DepositController::class, 'index'])->name('deposits.index');
    Route::post('/deposits', [DepositController::class, 'store'])->name('deposits.store');
    Route::get('/deposits/{deposit}/slip', [DepositController::class, 'slip'])->name('deposits.slip');
    Route::post('/deposits/{deposit}/approve', [DepositController::class, 'approve'])->name('deposits.approve');
    Route::post('/deposits/{deposit}/reject', [DepositController::class, 'reject'])->name('deposits.reject');

    // Message center / support
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{ticket}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{ticket}/reply', [MessageController::class, 'reply'])->name('messages.reply');
    Route::patch('/messages/{ticket}/status', [MessageController::class, 'updateStatus'])->name('messages.status');

    // Withdrawal requests
    Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::post('/withdrawals/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->name('withdrawals.approve');
    Route::post('/withdrawals/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->name('withdrawals.reject');

    // Admin settings (own profile + password)
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::get('/settings/security', [SettingsController::class, 'security'])->name('settings.security');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');

    // Pool / PnL
    Route::get('/pool', [PoolController::class, 'index'])->name('pool.index');
    Route::get('/pool/pnl', [PoolController::class, 'pnl'])->name('pool.pnl');
    Route::get('/pool/live', [PoolController::class, 'live'])->name('pool.live');
    Route::post('/pool', [PoolController::class, 'store'])->name('pool.store');
    Route::patch('/pool/{pool}', [PoolController::class, 'update'])->name('pool.update');
    Route::delete('/pool/{pool}', [PoolController::class, 'destroy'])->name('pool.destroy');
    Route::post('/pool/sync', [PoolController::class, 'sync'])->name('pool.sync');
});
