<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.settings', ['admin' => $request->user()]);
    }

    /** Dedicated Exchange Rate settings page (markup % applies to all currencies). */
    public function exchange()
    {
        $svc = app(\App\Services\SpotTradingService::class);
        $pct = $svc->markupPct();
        $liveInr = round($svc->usdInr() / (1 + $pct / 100), 4);

        // Every currency we have a live rate for (effective = live × markup), sorted A→Z.
        $samples = collect($svc->ratesMap())
            ->map(fn ($eff) => round((float) $eff, 4))
            ->sortKeys();

        return view('admin.settings-exchange', [
            'pct' => $pct,
            'liveInr' => $liveInr,
            'effInr' => $svc->usdInr(),
            'samples' => $samples,
        ]);
    }

    public function updateFx(Request $request)
    {
        $data = $request->validate(['fx_markup_pct' => ['required', 'numeric', 'min:0', 'max:50']]);
        Setting::put('fx_markup_pct', (float) $data['fx_markup_pct']);
        \Illuminate\Support\Facades\Cache::forget('fx.usdinr');
        \Illuminate\Support\Facades\Cache::forget('fx.rates.full');

        return back()->with('status', 'Exchange rate markup saved.');
    }

    public function branding()
    {
        return view('admin.settings-branding');
    }

    public function updateBranding(Request $request)
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:60'],
            'app_short_name' => ['nullable', 'string', 'max:30'],
            'app_slogan' => ['nullable', 'string', 'max:80'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:8192'],
            'login_hero' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:15360'],
        ]);

        Setting::put('app_name', $data['app_name']);
        Setting::put('app_short_name', $data['app_short_name'] ?: $data['app_name']);
        Setting::put('app_slogan', $data['app_slogan'] ?? 'Invest together · Earn together');

        $changed = false;

        // One logo upload — the favicon (browser tab) and the app/launch icon are derived from it.
        if ($request->hasFile('logo')) {
            $request->file('logo')->move(public_path(), 'logo.png');
            Setting::put('favicon_path', '/logo.png');
            $changed = true;
        }

        if ($request->hasFile('login_hero')) {
            $request->file('login_hero')->move(public_path(), 'login-hero.jpg');
            Setting::put('login_hero_path', '/login-hero.jpg');
            $changed = true;
        }

        // (Re)build the solid app/launch icon from the logo so iOS/Android don't add their own
        // square to the transparent logo. Regenerate when the logo changed or it's missing.
        if (is_file(public_path('logo.png')) && ($request->hasFile('logo') || ! is_file(public_path('app-icon.png')))) {
            if ($this->generateAppIcon(public_path('logo.png'), public_path('app-icon.png'), '#070b16')) {
                Setting::put('app_icon_path', '/app-icon.png');
            }
        }

        if ($changed) {
            Setting::put('brand_v', (string) now()->timestamp);
        }

        return back()->with('status', 'Branding updated.');
    }

    /** Composite the (transparent) logo onto a solid square so the home-screen/launch icon has no auto-added box. */
    private function generateAppIcon(string $src, string $dest, string $hex): bool
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagecreatefrompng')) {
            return false;
        }

        try {
            $logo = @imagecreatefrompng($src);
            if (! $logo) {
                return false;
            }

            $size = 512;
            $pad = (int) ($size * 0.16);
            $canvas = imagecreatetruecolor($size, $size);
            [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, $r, $g, $b));

            $lw = imagesx($logo);
            $lh = imagesy($logo);
            $box = $size - 2 * $pad;
            $scale = min($box / $lw, $box / $lh);
            $nw = (int) ($lw * $scale);
            $nh = (int) ($lh * $scale);
            $dx = (int) (($size - $nw) / 2);
            $dy = (int) (($size - $nh) / 2);

            imagealphablending($canvas, true);
            imagecopyresampled($canvas, $logo, $dx, $dy, 0, 0, $nw, $nh, $lw, $lh);
            imagepng($canvas, $dest);
            imagedestroy($canvas);
            imagedestroy($logo);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function security()
    {
        return view('admin.settings-security');
    }

    /** Market data / API keys (CubeX + Pool) — DB-managed, .env fallback. */
    public function marketData()
    {
        return view('admin.settings-marketdata', [
            'cubexPricesUrl' => Setting::get('cubex_prices_url', config('services.cubex.prices_url')),
            'cubexCandlesUrl' => Setting::get('cubex_candles_url', config('services.cubex.candles_url')),
            'cubexKey' => Setting::get('cubex_api_key', config('services.cubex.key')),
            'poolUrl' => Setting::get('pool_url', config('services.pool.url')),
            'poolKey' => Setting::get('pool_key', config('services.pool.key')),
        ]);
    }

    public function updateMarketData(Request $request)
    {
        $data = $request->validate([
            'cubex_prices_url' => ['nullable', 'url', 'max:255'],
            'cubex_candles_url' => ['nullable', 'url', 'max:255'],
            'cubex_api_key' => ['nullable', 'string', 'max:191'],
            'pool_url' => ['nullable', 'url', 'max:255'],
            'pool_key' => ['nullable', 'string', 'max:191'],
        ]);

        Setting::put('cubex_prices_url', trim((string) ($data['cubex_prices_url'] ?? '')));
        Setting::put('cubex_candles_url', trim((string) ($data['cubex_candles_url'] ?? '')));
        Setting::put('pool_url', trim((string) ($data['pool_url'] ?? '')));

        // Keys: only overwrite when a new value is typed (leave blank to keep current).
        if (! empty($data['cubex_api_key'])) {
            Setting::put('cubex_api_key', trim($data['cubex_api_key']));
        }
        if (! empty($data['pool_key'])) {
            Setting::put('pool_key', trim($data['pool_key']));
        }

        return back()->with('status', 'Market-data settings saved.');
    }

    public function testMarketData()
    {
        $cubex = app(\App\Services\CubexMarketClient::class);
        $lines = [];

        if (! $cubex->configured()) {
            $lines[] = 'CubeX: not configured (set prices URL + API key).';
        } else {
            $price = $cubex->prices(['EURUSD'])['EURUSD'] ?? null;
            $lines[] = $price ? "CubeX prices: OK ✔ (EURUSD = {$price})" : 'CubeX prices: FAILED ✖ (check key/URL).';
            $candles = $cubex->candles('EURUSD', '1h', 3);
            $lines[] = $candles ? 'CubeX candles: OK ✔ (' . count($candles) . ' bars).' : 'CubeX candles: FAILED ✖ (check candles URL).';
        }

        $pool = app(\App\Services\PoolApiClient::class);
        $lines[] = $pool->isLive() ? 'Pool API: URL + key configured ✔' : 'Pool API: not set (using simulated data).';

        return back()->with('status', implode('  ·  ', $lines));
    }

    /** Notify.lk SMS settings + live balance. */
    public function notify()
    {
        $svc = app(\App\Services\NotifyService::class);

        return view('admin.settings-notify', [
            'enabled' => (string) Setting::get('notify_enabled', config('notify.enabled') ? '1' : '0') === '1',
            'userId' => Setting::get('notify_user_id', config('notify.user_id')),
            'apiKey' => Setting::get('notify_api_key', config('notify.api_key')),
            'senderId' => Setting::get('notify_sender_id', config('notify.sender_id')),
            'numbers' => Setting::get('notify_numbers', ''),
            'onDeposit' => (string) Setting::get('notify_on_deposit', '1') === '1',
            'onWithdrawal' => (string) Setting::get('notify_on_withdrawal', '1') === '1',
            'onKyc' => (string) Setting::get('notify_on_kyc', '1') === '1',
            'configured' => $svc->configured(),
            'balance' => $svc->balance(),
        ]);
    }

    public function updateNotify(Request $request)
    {
        $data = $request->validate([
            'notify_user_id' => ['nullable', 'string', 'max:60'],
            'notify_api_key' => ['nullable', 'string', 'max:120'],
            'notify_sender_id' => ['nullable', 'string', 'max:40'],
            'notify_numbers' => ['nullable', 'string', 'max:3000'],
        ]);

        Setting::put('notify_enabled', $request->boolean('notify_enabled') ? '1' : '0');
        Setting::put('notify_user_id', trim((string) ($data['notify_user_id'] ?? '')));
        Setting::put('notify_sender_id', trim((string) ($data['notify_sender_id'] ?? '')));
        Setting::put('notify_numbers', trim((string) ($data['notify_numbers'] ?? '')));
        Setting::put('notify_on_deposit', $request->boolean('notify_on_deposit') ? '1' : '0');
        Setting::put('notify_on_withdrawal', $request->boolean('notify_on_withdrawal') ? '1' : '0');
        Setting::put('notify_on_kyc', $request->boolean('notify_on_kyc') ? '1' : '0');

        // Only overwrite the API key when a new one is typed (leave blank to keep current).
        if (! empty($data['notify_api_key'])) {
            Setting::put('notify_api_key', trim($data['notify_api_key']));
        }

        return back()->with('status', 'SMS settings saved.');
    }

    public function testNotify(Request $request)
    {
        $data = $request->validate([
            'test_phone' => ['required', 'string', 'max:20'],
            'test_message' => ['nullable', 'string', 'max:300'],
        ]);

        $msg = $data['test_message'] ?: ('Test SMS from ' . config('app.name', 'the platform') . ' — Notify.lk is working.');
        $res = app(\App\Services\NotifyService::class)->send($data['test_phone'], $msg);

        $ok = ($res['status'] ?? null) === 'success';

        return back()->with('status', ($ok ? 'Test SMS sent ✔ ' : 'Notify.lk responded: ') . json_encode($res));
    }

    public function updateProfile(Request $request)
    {
        $admin = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($admin->id)],
        ]);

        $admin->update($data);

        return back()->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => $request->password]);

        return back()->with('status', 'Password changed.');
    }
}
