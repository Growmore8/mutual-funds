<!DOCTYPE html>
<html lang="en" class="h-full dark">
@php $appName = \App\Models\Setting::get('app_name', 'GrowthCapital'); $brandV = \App\Models\Setting::get('brand_v', '1'); @endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Complete your profile · {{ $appName }}</title>
    <link rel="icon" href="/logo.png?v={{ $brandV }}" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .gi{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);color:#fff}
        .gi::placeholder{color:#9aa3b2}
        .gi:focus{border-color:#10b981;box-shadow:0 0 0 2px rgba(16,185,129,.25);outline:none}
        .gi option{color:#111827}
    </style>
</head>
<body class="h-full bg-[#070b16] text-gray-200">
<div class="flex items-center justify-center p-4" style="min-height:100vh;min-height:100dvh">
    <div class="w-full max-w-md rounded-3xl p-7" style="background:#0f1b38;box-shadow:0 20px 60px rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.08)">
        <div class="flex items-center gap-2 mb-5">
            <img src="/logo.png?v={{ $brandV }}" class="w-9 h-9" onerror="this.style.display='none'">
            <span class="text-xl font-extrabold text-white">{{ $appName }}</span>
        </div>

        <h1 class="text-2xl font-extrabold text-white">Complete your registration</h1>
        <p class="text-sm text-gray-400 mt-1 mb-5">Hi {{ $user->name }} — just a few details to finish setting up your account.</p>

        @if ($errors->any())
            <div class="mb-4 bg-red-500/10 border border-red-500/30 text-red-300 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('onboarding.store') }}" class="space-y-4 text-sm">
            @csrf
            <div>
                <label class="block text-gray-300 mb-1">Full name (as per ID)</label>
                <input name="name" value="{{ old('name', $user->name) }}" required class="gi w-full rounded-xl px-3 py-3">
            </div>
            <div>
                <label class="block text-gray-300 mb-1">Email</label>
                <input value="{{ $user->email }}" disabled class="w-full rounded-xl px-3 py-3 text-gray-400" style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08)">
            </div>
            <div>
                <label class="block text-gray-300 mb-1">Phone</label>
                <input name="phone" value="{{ old('phone') }}" required placeholder="+91 …" class="gi w-full rounded-xl px-3 py-3">
            </div>
            <div>
                <label class="block text-gray-300 mb-1">Country</label>
                <input name="country" value="{{ old('country') }}" required placeholder="e.g. India" class="gi w-full rounded-xl px-3 py-3">
            </div>
            <div>
                <label class="block text-gray-300 mb-1">Choose your plan</label>
                <select name="account_type_id" required class="gi w-full rounded-xl px-3 py-3">
                    <option value="">Select a plan…</option>
                    @foreach ($accountTypes as $at)
                        <option value="{{ $at->id }}" @selected(old('account_type_id')==$at->id)>{{ $at->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-3 rounded-xl transition">Finish &amp; continue</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
            @csrf
            <button class="text-xs text-gray-500 hover:text-gray-300">Sign out</button>
        </form>
    </div>
</div>
</body>
</html>
