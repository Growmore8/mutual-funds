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
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
            'favicon' => ['nullable', 'image', 'mimes:png,ico,jpg,jpeg', 'max:1024'],
            'login_hero' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:15360'],
            'app_icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:4096'],
        ]);

        Setting::put('app_name', $data['app_name']);
        Setting::put('app_short_name', $data['app_short_name'] ?: $data['app_name']);
        Setting::put('app_slogan', $data['app_slogan'] ?? 'Invest together · Earn together');

        // Uploaded images overwrite the public files; bump a version for cache-busting.
        if ($request->hasFile('logo')) {
            $request->file('logo')->move(public_path(), 'logo.png');
        }
        if ($request->hasFile('favicon')) {
            $request->file('favicon')->move(public_path(), 'favicon.png');
            Setting::put('favicon_path', '/favicon.png');
        }
        if ($request->hasFile('login_hero')) {
            $request->file('login_hero')->move(public_path(), 'login-hero.jpg');
            Setting::put('login_hero_path', '/login-hero.jpg');
        }
        if ($request->hasFile('app_icon')) {
            $request->file('app_icon')->move(public_path(), 'app-icon.png');
            Setting::put('app_icon_path', '/app-icon.png');
        } elseif (is_file(public_path('logo.png'))) {
            // No custom icon uploaded — auto-build a solid (non-transparent) launch icon
            // from the logo so iOS/Android don't add their own square to the transparent logo.
            if ($this->generateAppIcon(public_path('logo.png'), public_path('app-icon.png'), '#070b16')) {
                Setting::put('app_icon_path', '/app-icon.png');
            }
        }
        if ($request->hasFile('logo') || $request->hasFile('favicon') || $request->hasFile('login_hero') || $request->hasFile('app_icon')) {
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
