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
        if ($request->hasFile('logo') || $request->hasFile('favicon') || $request->hasFile('login_hero')) {
            Setting::put('brand_v', (string) now()->timestamp);
        }

        return back()->with('status', 'Branding updated.');
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
