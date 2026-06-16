<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Unlock · GrowthCapital Funds</title>
    <meta name="theme-color" content="#0a1730">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-[#0a1730] text-white">
<div class="min-h-full flex flex-col items-center justify-center px-6">
    <div class="w-full max-w-xs text-center">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-white/10 grid place-items-center mb-5">
            <i class="fa-solid fa-lock text-2xl text-emerald-400"></i>
        </div>
        <h1 class="text-xl font-bold">Welcome back, {{ explode(' ', $user->name)[0] }}</h1>
        <p class="text-sm text-gray-400 mt-1 mb-7">Enter your PIN to unlock</p>

        @if ($errors->any())
            <div class="mb-4 bg-red-500/15 border border-red-400/30 text-red-200 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('lock.unlock') }}">
            @csrf
            <input type="password" name="pin" inputmode="numeric" pattern="[0-9]*" maxlength="6" autofocus required
                   class="w-full text-center tracking-[0.6em] text-2xl bg-white/10 border border-white/20 rounded-xl py-3 text-white placeholder-white/30 focus:ring-emerald-500 focus:border-emerald-500"
                   placeholder="••••">
            <button class="w-full mt-4 bg-emerald-500 hover:bg-emerald-600 text-[#04231a] font-semibold py-3 rounded-xl">
                Unlock
            </button>
        </form>

        {{-- Biometric unlock mounts here when a passkey is registered (added in biometric build). --}}
        <div id="biometric-unlock" class="mt-4 hidden">
            <button type="button" id="biometric-btn" class="w-full bg-white/10 hover:bg-white/15 text-white font-medium py-3 rounded-xl">
                <i class="fa-solid fa-fingerprint mr-1"></i> Unlock with biometrics
            </button>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mt-8">@csrf
            <button class="text-sm text-gray-400 hover:text-white">Not you? Log out</button>
        </form>
    </div>
</div>
</body>
</html>
