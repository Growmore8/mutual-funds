<?php

use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Dynamic PWA manifest (name/icon come from admin Branding settings).
Route::get('/manifest.webmanifest', function () {
    $v = \App\Models\Setting::get('brand_v', '1');
    $icon = \App\Models\Setting::get('app_icon_path', '/logo.png');

    return response()->json([
        'name' => \App\Models\Setting::get('app_name', 'GrowthCapital'),
        'short_name' => \App\Models\Setting::get('app_short_name', 'GC Fund'),
        'description' => 'Your managed mutual-fund pool account — invest, track daily profit, and withdraw.',
        'start_url' => '/app',
        'scope' => '/',
        'display' => 'standalone',
        'orientation' => 'portrait',
        'background_color' => '#070b16',
        'theme_color' => '#070b16',
        'icons' => [
            ['src' => $icon . '?v=' . $v, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $icon . '?v=' . $v, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $icon . '?v=' . $v, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
    ])->header('Content-Type', 'application/manifest+json');
});

// iOS PWA launch image (apple-touch-startup-image): a dark splash with the centered logo,
// so the cold-launch screen matches our loading screen instead of iOS adding an icon square.
Route::get('/apple-splash', function (\Illuminate\Http\Request $r) {
    $w = max(320, min(2400, (int) $r->get('w', 1170)));
    $h = max(480, min(3200, (int) $r->get('h', 2532)));

    abort_unless(function_exists('imagecreatetruecolor'), 404);

    $img = imagecreatetruecolor($w, $h);

    // Vertical gradient #0a1f1b -> #070b16 to match the in-app splash.
    $top = [0x0a, 0x1f, 0x1b];
    $bot = [0x07, 0x0b, 0x16];
    for ($y = 0; $y < $h; $y++) {
        $t = $y / max(1, $h);
        $c = imagecolorallocate($img,
            (int) ($top[0] + ($bot[0] - $top[0]) * $t),
            (int) ($top[1] + ($bot[1] - $top[1]) * $t),
            (int) ($top[2] + ($bot[2] - $top[2]) * $t));
        imageline($img, 0, $y, $w, $y, $c);
    }

    // Small centered logo (same size/position as the in-app splash logo box).
    $logoPath = public_path('logo.png');
    if (function_exists('imagecreatefrompng') && is_file($logoPath) && ($logo = @imagecreatefrompng($logoPath))) {
        $lw = imagesx($logo);
        $lh = imagesy($logo);
        $target = (int) ($w * 0.16);
        $scale = $target / $lw;
        $nw = (int) ($lw * $scale);
        $nh = (int) ($lh * $scale);
        $dx = (int) (($w - $nw) / 2);
        $dy = (int) ($h * 0.44 - $nh / 2);
        imagealphablending($img, true);
        imagecopyresampled($img, $logo, $dx, $dy, 0, 0, $nw, $nh, $lw, $lh);
        imagedestroy($logo);
    }

    ob_start();
    imagepng($img);
    $png = ob_get_clean();
    imagedestroy($img);

    return response($png, 200)
        ->header('Content-Type', 'image/png')
        ->header('Cache-Control', 'public, max-age=86400');
})->name('apple.splash');

// Biometric (passkey) passwordless login — usable on the login page before auth.
Route::post('/webauthn/login/options', [\App\Http\Controllers\WebAuthnController::class, 'loginOptions'])->name('webauthn.login.options');
Route::post('/webauthn/login', [\App\Http\Controllers\WebAuthnController::class, 'login'])->name('webauthn.login');

// Fast login with app PIN (after the user has set one).
Route::post('/pin-login', [\App\Http\Controllers\Auth\PinLoginController::class, 'login'])->name('pin.login');

// Social login (Google) via Socialite.
Route::get('/auth/{provider}/redirect', [\App\Http\Controllers\Auth\OAuthController::class, 'redirect'])->name('oauth.redirect');
Route::get('/auth/{provider}/callback', [\App\Http\Controllers\Auth\OAuthController::class, 'callback'])->name('oauth.callback');

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
            ->get(['name', 'slug', 'min_deposit', 'max_deposit', 'pool_amount', 'daily_return_pct', 'profit_share_pct', 'lock_in_months', 'description', 'features'])
            ->map(fn ($t) => [
                'name' => $t->name,
                'slug' => $t->slug,
                'min_deposit' => (float) $t->min_deposit,
                'max_deposit' => $t->max_deposit !== null ? (float) $t->max_deposit : null,
                'pool_amount' => (float) $t->pool_amount,
                'daily_return_pct' => (float) $t->daily_return_pct,
                'daily_profit_cap' => round((float) $t->pool_amount * (float) $t->daily_return_pct / 100, 2),
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
    // Web push subscriptions (clients + admins)
    Route::post('/push/subscribe', [\App\Http\Controllers\PushController::class, 'subscribe'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [\App\Http\Controllers\PushController::class, 'unsubscribe'])->name('push.unsubscribe');

    // Email OTP verification
    Route::get('/verify-otp', [OtpController::class, 'show'])->name('otp.show');
    Route::post('/verify-otp', [OtpController::class, 'verify'])->name('otp.verify');
    Route::post('/verify-otp/resend', [OtpController::class, 'resend'])->name('otp.resend');

    // KYC upload + status (requires OTP verified first)
    Route::get('/kyc', [KycController::class, 'show'])->name('kyc.show');
    Route::post('/kyc', [KycController::class, 'store'])->name('kyc.store');

    // Notifications (bell) — admin + client share these
    Route::get('/notifications/feed', [\App\Http\Controllers\NotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifications/read', [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.read');

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

    // Client app — only reachable once KYC is approved (onboarded), and gated
    // by the app-lock (PIN/biometric) when configured.
    Route::middleware(['onboarded', 'locked'])->group(function () {
        // Client dashboard
        Route::get('/app', [\App\Http\Controllers\ClientDashboardController::class, 'index'])->name('client.dashboard');
        Route::get('/app/live', [\App\Http\Controllers\ClientDashboardController::class, 'live'])->name('client.live');

        // My account (read-only; account type is managed by admin)
        Route::get('/accounts', [\App\Http\Controllers\AccountRequestController::class, 'index'])->name('accounts.index');
        Route::post('/accounts', [\App\Http\Controllers\AccountRequestController::class, 'store'])->middleware('notlocked')->name('accounts.store');
        Route::post('/accounts/switch/{account}', [\App\Http\Controllers\AccountRequestController::class, 'switchAccount'])->name('accounts.switch');

        // Deposit (client submits with slip -> admin approves)
        Route::get('/deposit', [\App\Http\Controllers\ClientDepositController::class, 'create'])->name('client.deposit.create');
        Route::post('/deposit', [\App\Http\Controllers\ClientDepositController::class, 'store'])->middleware('notlocked')->name('client.deposit.store');

        // Withdrawals (profit only; request -> admin approval)
        Route::get('/withdraw', [\App\Http\Controllers\WithdrawalController::class, 'create'])->name('withdraw.create');
        Route::post('/withdraw', [\App\Http\Controllers\WithdrawalController::class, 'store'])->middleware('notlocked')->name('withdraw.store');

        // Referrals
        Route::get('/referrals', function (\Illuminate\Http\Request $request) {
            $user = $request->user();

            return view('client.referrals', [
                'user' => $user,
                'referrals' => $user->referrals()->latest()->get(),
                'earned' => $user->referralEarned(),
            ]);
        })->name('client.referrals');

        // Client payout (withdrawal) methods
        Route::get('/payout-methods', [\App\Http\Controllers\WithdrawalMethodController::class, 'index'])->name('payout.index');
        Route::post('/payout-methods', [\App\Http\Controllers\WithdrawalMethodController::class, 'store'])->middleware('notlocked')->name('payout.store');
        Route::delete('/payout-methods/{payout}', [\App\Http\Controllers\WithdrawalMethodController::class, 'destroy'])->middleware('notlocked')->name('payout.destroy');

        // Statements
        Route::get('/transactions', [\App\Http\Controllers\StatementController::class, 'transactions'])->name('client.transactions');
        Route::get('/profit', [\App\Http\Controllers\StatementController::class, 'profit'])->name('client.profit');
        Route::get('/statement', [\App\Http\Controllers\StatementController::class, 'statement'])->middleware('notlocked')->name('client.statement');

        // Support tickets / message center
        Route::get('/support', [\App\Http\Controllers\SupportController::class, 'index'])->name('support.index');
        Route::get('/support/new', [\App\Http\Controllers\SupportController::class, 'create'])->name('support.create');
        Route::post('/support', [\App\Http\Controllers\SupportController::class, 'store'])->name('support.store');
        Route::get('/support/{ticket}', [\App\Http\Controllers\SupportController::class, 'show'])->name('support.show');
        Route::post('/support/{ticket}/reply', [\App\Http\Controllers\SupportController::class, 'reply'])->name('support.reply');

        // Spot Trading (separate module — uses Twelve Data, not the mutual-fund pool)
        Route::get('/markets', [\App\Http\Controllers\SpotController::class, 'markets'])->name('markets.index');
        Route::get('/markets/quotes', [\App\Http\Controllers\SpotController::class, 'marketQuotes'])->name('markets.quotes');
        Route::get('/spot', [\App\Http\Controllers\SpotController::class, 'index'])->name('spot.index');
        Route::get('/spot/quote', [\App\Http\Controllers\SpotController::class, 'quote'])->name('spot.quote');
        Route::get('/spot/candles', [\App\Http\Controllers\SpotController::class, 'candles'])->name('spot.candles');
        Route::get('/spot/book', [\App\Http\Controllers\SpotController::class, 'book'])->name('spot.book');
        Route::post('/spot/order', [\App\Http\Controllers\SpotController::class, 'order'])->middleware('notlocked')->name('spot.order');
        Route::post('/spot/order/{order}/cancel', [\App\Http\Controllers\SpotController::class, 'cancel'])->middleware('notlocked')->name('spot.cancel');

        // P2P (managed merchants)
        Route::get('/p2p', [\App\Http\Controllers\P2pController::class, 'index'])->name('p2p.index');
        Route::post('/p2p/order', [\App\Http\Controllers\P2pController::class, 'order'])->middleware('notlocked')->name('p2p.order');

        // Within-account transfer: Mutual Fund <-> Spot (single USD base)
        Route::get('/transfer', [\App\Http\Controllers\TransferController::class, 'create'])->name('transfer.create');
        Route::post('/transfer', [\App\Http\Controllers\TransferController::class, 'store'])->middleware('notlocked')->name('transfer.store');

        // Profile (Breeze)
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
