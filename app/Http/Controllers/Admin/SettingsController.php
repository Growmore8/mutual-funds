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
