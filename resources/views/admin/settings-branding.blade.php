<x-admin-layout title="Branding">
    <div class="max-w-2xl">
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Branding</h2>
        <p class="text-sm text-gray-500 mb-5">App name, logo and favicon shown across the client app and login.</p>

        @if (session('status'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.settings.branding.update') }}" enctype="multipart/form-data" class="bg-white shadow rounded-xl p-6 space-y-5">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">App name</label>
                    <input name="app_name" value="{{ old('app_name', \App\Models\Setting::get('app_name', 'GrowthCapital')) }}" class="mt-1 w-full border-gray-300 rounded-md" required>
                    <p class="text-xs text-gray-400 mt-1">Shown in headers, titles &amp; emails.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Short name (home-screen)</label>
                    <input name="app_short_name" value="{{ old('app_short_name', \App\Models\Setting::get('app_short_name', 'GC Fund')) }}" class="mt-1 w-full border-gray-300 rounded-md">
                    <p class="text-xs text-gray-400 mt-1">Used as the installed PWA icon label.</p>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Slogan</label>
                <input name="app_slogan" value="{{ old('app_slogan', \App\Models\Setting::get('app_slogan', 'Invest together · Earn together')) }}" class="mt-1 w-full border-gray-300 rounded-md">
                <p class="text-xs text-gray-400 mt-1">Shown on the loading screen under the app name.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Logo</label>
                    <div class="flex items-center gap-3 mt-1">
                        <img src="/logo.png?v={{ \App\Models\Setting::get('brand_v', '1') }}" alt="" class="w-12 h-12 rounded-lg border border-gray-200 object-contain bg-gray-50">
                        <input type="file" name="logo" accept=".png,.jpg,.jpeg,.svg" class="text-xs file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-gray-100">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Square PNG recommended. Replaces the app logo &amp; PWA icon.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Favicon</label>
                    <div class="flex items-center gap-3 mt-1">
                        <img src="{{ \App\Models\Setting::get('favicon_path', '/logo.png') }}?v={{ \App\Models\Setting::get('brand_v', '1') }}" alt="" class="w-8 h-8 rounded border border-gray-200 object-contain bg-gray-50">
                        <input type="file" name="favicon" accept=".png,.ico,.jpg,.jpeg" class="text-xs file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-gray-100">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Small square PNG. Shown in the browser tab.</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Login background image</label>
                <div class="flex items-center gap-3 mt-1">
                    <img src="{{ \App\Models\Setting::get('login_hero_path', '/logo.png') }}?v={{ \App\Models\Setting::get('brand_v', '1') }}" alt="" class="w-24 h-14 rounded-lg border border-gray-200 object-cover bg-gray-50">
                    <input type="file" name="login_hero" accept=".png,.jpg,.jpeg,.webp" class="text-xs file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-gray-100">
                </div>
                <p class="text-xs text-gray-400 mt-1">Wide photo (e.g. lifestyle/family), up to 15&nbsp;MB. Shown on the login &amp; sign-up screens with a dark fade. Leave empty for the default gradient.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">App icon (home screen / launch)</label>
                <div class="flex items-center gap-3 mt-1">
                    <img src="{{ \App\Models\Setting::get('app_icon_path', '/logo.png') }}?v={{ \App\Models\Setting::get('brand_v', '1') }}" alt="" class="w-12 h-12 rounded-2xl border border-gray-200 object-cover bg-[#070b16]">
                    <input type="file" name="app_icon" accept=".png,.jpg,.jpeg" class="text-xs file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-gray-100">
                </div>
                <p class="text-xs text-gray-400 mt-1"><strong>Square (e.g. 512×512) with a solid background</strong> — this is the installed icon iOS/Android show on the home screen &amp; launch. Use a full-bleed design (logo on a dark/brand tile) so the launch screen looks intentional. If empty, the transparent logo is used (iOS will add a black square).</p>
            </div>

            <button class="px-5 py-2 bg-emerald-600 text-white rounded-md text-sm font-medium">Save branding</button>
        </form>
        <p class="text-xs text-gray-400 mt-3">After saving, clients may need to hard-refresh (or reinstall the PWA) to see a new logo/icon.</p>
    </div>
</x-admin-layout>
