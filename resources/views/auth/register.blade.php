<!DOCTYPE html>
<html lang="en" class="h-full dark">
@php $appName = \App\Models\Setting::get('app_name', 'GrowthCapital'); $brandV = \App\Models\Setting::get('brand_v', '1'); $hero = \App\Models\Setting::get('login_hero_path'); @endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign up · {{ $appName }}</title>
    <link rel="icon" href="/logo.png?v={{ $brandV }}" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .auth-hero{background:radial-gradient(1200px 500px at 20% -10%,rgba(37,99,235,.35),transparent 55%),radial-gradient(900px 500px at 90% 110%,rgba(16,185,129,.3),transparent 55%),linear-gradient(160deg,#0b1224,#070b16)}
        .auth-blob{filter:blur(40px);opacity:.5}
        .ginput{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1)}
        .ginput:focus{border-color:#10b981;box-shadow:0 0 0 2px rgba(16,185,129,.25);background:rgba(255,255,255,.06)}
        select.ginput option{background:#0b1224;color:#e5e7eb}
    </style>
</head>
<body class="h-full bg-[#070b16] text-gray-200">
<div class="min-h-full lg:grid lg:grid-cols-2">

    {{-- Brand / art side (desktop) --}}
    <div class="hidden lg:block auth-hero relative overflow-hidden" @if($hero) style="background-image:linear-gradient(120deg,rgba(7,11,22,.93),rgba(7,11,22,.5)),url('{{ $hero }}?v={{ $brandV }}');background-size:cover;background-position:center" @endif>
        @unless($hero)
        <div class="absolute -top-10 left-10 w-72 h-72 rounded-full bg-blue-500/40 auth-blob"></div>
        <div class="absolute bottom-0 right-0 w-80 h-80 rounded-full bg-emerald-500/40 auth-blob"></div>
        @endunless
        <div class="relative z-10 h-full flex flex-col justify-center px-16 text-white">
            <h2 class="text-4xl font-extrabold leading-tight">Start investing<br>with confidence.</h2>
            <p class="text-gray-300 mt-4 max-w-md">Create your account, get verified, and your capital starts earning a daily share of the managed pool's profit.</p>
            <p class="absolute bottom-8 text-xs text-gray-500">&copy; {{ date('Y') }} {{ $appName }} Ltd</p>
        </div>
    </div>

    {{-- Form side --}}
    <div class="relative lg:auth-hero lg:flex lg:items-center lg:justify-center lg:px-6 lg:py-10">
        @if($hero)
            <div class="lg:hidden relative">
                <img src="{{ $hero }}?v={{ $brandV }}" alt="" class="w-full block" style="-webkit-user-drag:none">
                <div class="absolute inset-x-0 bottom-0 top-1/4" style="background:linear-gradient(to bottom,rgba(7,11,22,0),#070b16 85%,#070b16 100%)"></div>
            </div>
        @endif
        <div class="w-full max-w-sm mx-auto px-6 pb-10 relative z-10 {{ $hero ? '-mt-20 lg:mt-0 lg:px-0' : 'pt-8 lg:pt-0 lg:px-0' }}">
            <div class="flex items-center gap-2 mb-6">
                <img src="/logo.png?v={{ $brandV }}" class="w-9 h-9" onerror="this.style.display='none'">
                <span class="text-xl font-extrabold tracking-wide text-white">{{ $appName }}</span>
            </div>

            <h1 class="text-2xl font-extrabold text-white">Create your account</h1>
            <p class="text-sm text-gray-400 mt-1 mb-5">It only takes a minute.</p>

            @if ($errors->any())
                <div class="mb-4 bg-red-500/10 border border-red-500/30 text-red-300 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
            @endif

            <a href="{{ route('oauth.redirect', 'google') }}" class="w-full flex items-center justify-center gap-2 py-3 rounded-xl border border-white/15 hover:bg-white/5 text-sm font-medium text-gray-200 mb-4">
                <i class="fa-brands fa-google text-[#ea4335]"></i> Sign up with Google
            </a>
            <div class="relative my-4 text-center"><span class="text-xs text-gray-500 bg-[#070b16] lg:bg-transparent px-2 relative z-10">Or with email</span><div class="absolute inset-x-0 top-1/2 border-t border-white/10"></div></div>

            <form method="POST" action="{{ route('register') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="ref" value="{{ $ref ?? request('ref') }}">
                @if (!empty($ref))
                    <div class="text-xs bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 rounded-lg p-2.5"><i class="fa-solid fa-gift"></i> Joining with referral code <strong>{{ $ref }}</strong>.</div>
                @endif

                <input name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Full name (as per ID)" class="ginput w-full px-4 py-3 rounded-xl text-white placeholder-gray-500 outline-none">
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="Email" class="ginput w-full px-4 py-3 rounded-xl text-white placeholder-gray-500 outline-none">
                <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="Phone (+44 …)" class="ginput w-full px-4 py-3 rounded-xl text-white placeholder-gray-500 outline-none">
                <x-country-select name="country" :value="old('country')" :required="true" class="ginput w-full px-4 py-3 rounded-xl text-white outline-none" />
                <select name="account_type_id" required class="ginput w-full px-4 py-3 rounded-xl text-white outline-none">
                    <option value="">Select a plan…</option>
                    @foreach ($accountTypes as $t)
                        <option value="{{ $t->id }}" @selected(old('account_type_id') == $t->id)>{{ $t->name }} — min ${{ number_format((float)$t->min_deposit) }}</option>
                    @endforeach
                </select>
                <div class="relative" x-data="{ show:false }">
                    <input :type="show?'text':'password'" name="password" required autocomplete="new-password" placeholder="Password" class="ginput w-full px-4 pr-11 py-3 rounded-xl text-white placeholder-gray-500 outline-none">
                    <button type="button" @click="show=!show" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-500"><i class="fa-solid" :class="show?'fa-eye-slash':'fa-eye'"></i></button>
                </div>
                <input type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Confirm password" class="ginput w-full px-4 py-3 rounded-xl text-white placeholder-gray-500 outline-none">

                <button class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-3 rounded-xl transition mt-1">Create account</button>
            </form>

            <p class="text-center text-sm text-gray-400 mt-5">Already have an account? <a href="{{ route('login') }}" class="text-emerald-400 font-semibold">Log in</a></p>
        </div>
    </div>
</div>
</body>
</html>
