<!DOCTYPE html>
<html lang="en" class="h-full dark">
@php $appName = \App\Models\Setting::get('app_name', 'GrowthCapital'); $brandV = \App\Models\Setting::get('brand_v', '1'); $hero = \App\Models\Setting::get('login_hero_path'); @endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Log in · {{ $appName }}</title>
    <link rel="icon" href="/logo.png?v={{ $brandV }}" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .auth-hero{background:radial-gradient(1200px 500px at 20% -10%,rgba(37,99,235,.35),transparent 55%),radial-gradient(900px 500px at 90% 110%,rgba(16,185,129,.3),transparent 55%),linear-gradient(160deg,#0b1224,#070b16)}
        /* Inputs: dark glass on mobile (over the photo) → light on the desktop card */
        .ginput{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);color:#fff}
        .ginput::placeholder{color:#9aa3b2}
        .ginput:focus{border-color:#10b981;box-shadow:0 0 0 2px rgba(16,185,129,.25)}
        @media (min-width:1024px){
            .ginput{background:#f8fafc;border-color:#e5e7eb;color:#111827}
            .ginput::placeholder{color:#9ca3af}
            .ginput:focus{background:#fff}
        }
    </style>
</head>
<body class="h-full bg-[#070b16] text-gray-200">

{{-- MOBILE: full-bleed faded brand photo behind the form --}}
@if($hero)
<div class="lg:hidden fixed inset-0" style="z-index:0;background-image:linear-gradient(to bottom,rgba(7,11,22,.28) 0%,rgba(7,11,22,.50) 45%,rgba(7,11,22,.90) 100%),url('{{ $hero }}?v={{ $brandV }}');background-size:cover;background-position:center 30%;background-repeat:no-repeat"></div>
@endif

<div class="relative z-10 flex items-center justify-center lg:p-8 {{ $hero ? '' : 'auth-hero' }}" style="min-height:100vh;min-height:100dvh">
    <div class="w-full lg:max-w-5xl lg:grid lg:grid-cols-2 lg:bg-white lg:rounded-3xl lg:shadow-2xl lg:overflow-hidden">

        {{-- FORM COLUMN --}}
        <div class="flex flex-col justify-end lg:justify-center min-h-screen lg:min-h-0 lg:py-14">
            <div class="w-full max-w-sm mx-auto px-6 lg:px-10 pt-40 lg:pt-0 relative z-10 text-center lg:text-left" style="padding-bottom:max(2.25rem,env(safe-area-inset-bottom))">
                <div class="flex items-center justify-center lg:justify-start gap-2 mb-5 lg:mb-8">
                    <img src="/logo.png?v={{ $brandV }}" class="w-9 h-9" onerror="this.style.display='none'">
                    <span class="text-2xl lg:text-xl font-extrabold tracking-wide text-white lg:text-[#0a1730]">{{ $appName }}</span>
                </div>

                <h1 class="text-3xl font-extrabold text-white lg:text-[#0a1730] leading-tight drop-shadow lg:drop-shadow-none">Login to your account</h1>
                <p class="text-sm text-gray-200 lg:text-gray-500 mt-2 mb-6 drop-shadow lg:drop-shadow-none">Enter your login information</p>

                @if (session('status'))
                    <div class="mb-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 lg:text-emerald-700 lg:bg-emerald-50 lg:border-emerald-200 text-sm rounded-lg p-3">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 bg-red-500/10 border border-red-500/30 text-red-300 lg:text-red-700 lg:bg-red-50 lg:border-red-200 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-3">
                    @csrf
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                               class="ginput w-full pl-11 py-3 rounded-xl outline-none" placeholder="Email">
                    </div>

                    <div class="relative" x-data="{ show: false }">
                        <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input id="password" :type="show ? 'text' : 'password'" name="password" required autocomplete="current-password"
                               class="ginput w-full pl-11 pr-11 py-3 rounded-xl outline-none" placeholder="Password">
                        <button type="button" @click="show=!show" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"><i class="fa-solid" :class="show?'fa-eye-slash':'fa-eye'"></i></button>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-300 lg:text-gray-600">
                            <input type="checkbox" name="remember" class="rounded bg-white/5 border-white/20 lg:bg-white lg:border-gray-300 text-emerald-500 focus:ring-emerald-500"> Remember me
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm text-emerald-400 lg:text-emerald-600 hover:underline">Forgot password</a>
                        @endif
                    </div>

                    <button class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-3 rounded-xl transition mt-2">LOGIN</button>
                </form>

                {{-- Biometric fast login --}}
                <div id="bio-login-wrap" class="hidden mt-3">
                    <button type="button" id="bio-login-btn" class="w-full border border-white/15 lg:border-gray-300 text-gray-200 lg:text-gray-700 font-medium py-3 rounded-xl flex items-center justify-center gap-2 hover:bg-white/5 lg:hover:bg-gray-50">
                        <i class="fa-solid fa-fingerprint text-emerald-400 lg:text-emerald-600"></i> Face ID / fingerprint
                    </button>
                    <p id="bio-login-msg" class="text-xs text-center text-red-400 mt-2 hidden"></p>
                </div>

                {{-- Fast login with PIN --}}
                <div id="pin-login-wrap" class="hidden mt-3" x-data="{ open: false }">
                    <button type="button" @click="open=!open; if(open) $nextTick(()=>document.getElementById('pin-login-input').focus())"
                            class="w-full border border-white/15 lg:border-gray-300 text-gray-200 lg:text-gray-700 font-medium py-3 rounded-xl flex items-center justify-center gap-2 hover:bg-white/5 lg:hover:bg-gray-50">
                        <i class="fa-solid fa-keyboard text-emerald-400 lg:text-emerald-600"></i> Login with PIN
                    </button>
                    <form x-show="open" x-transition method="POST" action="{{ route('pin.login') }}" class="mt-3" style="display:none">
                        @csrf
                        <input type="hidden" name="email" id="pin-login-email">
                        <input id="pin-login-input" name="pin" type="password" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="off"
                               class="ginput w-full text-center tracking-[0.6em] text-xl py-3 rounded-xl outline-none" placeholder="••••">
                        <button class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-3 rounded-xl transition mt-2">Unlock &amp; login</button>
                        <p class="text-[11px] text-gray-500 text-center mt-2">Logs in <span id="pin-login-name" class="text-gray-400 lg:text-gray-600"></span> on this device.</p>
                    </form>
                </div>

                <div class="relative my-5 text-center"><span class="text-xs text-gray-400 lg:text-gray-500 bg-transparent lg:bg-white px-2 relative z-10">Or</span><div class="absolute inset-x-0 top-1/2 border-t border-white/10 lg:border-gray-200"></div></div>

                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('oauth.redirect', 'google') }}" class="flex items-center justify-center gap-2 py-3 rounded-xl border border-white/15 lg:border-gray-300 hover:bg-white/5 lg:hover:bg-gray-50 text-sm font-medium text-gray-200 lg:text-gray-700">
                        <i class="fa-brands fa-google text-[#ea4335]"></i> Google
                    </a>
                    <button type="button" onclick="alert('Apple Sign-In is coming soon.')" class="flex items-center justify-center gap-2 py-3 rounded-xl border border-white/15 lg:border-gray-300 hover:bg-white/5 lg:hover:bg-gray-50 text-sm font-medium text-gray-200 lg:text-gray-700">
                        <i class="fa-brands fa-apple lg:text-gray-900"></i> Apple
                    </button>
                </div>

                @if (Route::has('register'))
                    <p class="text-center text-sm text-gray-400 lg:text-gray-500 mt-6">Don't have an account? <a href="{{ route('register') }}" class="text-emerald-400 lg:text-emerald-600 font-semibold">Sign Up</a></p>
                @endif
            </div>
        </div>

        {{-- BRAND IMAGE COLUMN (desktop only) --}}
        <div class="hidden lg:block relative m-3 rounded-2xl overflow-hidden"
             @if($hero) style="background-image:url('{{ $hero }}?v={{ $brandV }}');background-size:cover;background-position:68% center" @else style="background:linear-gradient(150deg,#0b3b32,#0a1730)" @endif>
            <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(7,11,22,.45),rgba(7,11,22,0) 55%)"></div>
            <div class="absolute bottom-8 left-8 right-8 text-white">
                <h2 class="text-2xl font-extrabold leading-tight">Invest together.<br>Earn together.</h2>
                <p class="text-sm text-gray-200/90 mt-2">Professionally managed pool · daily profit share.</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@laragear/webpass@2/dist/webpass.js"></script>
<script>
    (function () {
        var wrap = document.getElementById('bio-login-wrap'), btn = document.getElementById('bio-login-btn'), msg = document.getElementById('bio-login-msg');
        if (!wrap || typeof Webpass === 'undefined' || (Webpass.isUnsupported && Webpass.isUnsupported())) return;
        if (window.PublicKeyCredential && PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable) {
            PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().then(function (ok) { if (ok) wrap.classList.remove('hidden'); });
        }
        btn.addEventListener('click', async function () {
            msg.classList.add('hidden'); btn.disabled = true;
            try {
                const email = (document.getElementById('email') || {}).value || '';
                const { success, data } = await Webpass.assert({ path: '{{ route('webauthn.login.options') }}', body: email ? { email } : {} }, '{{ route('webauthn.login') }}');
                if (success) { window.location.href = (data && data.redirect) ? data.redirect : '{{ route('dashboard') }}'; }
                else { msg.textContent = 'Biometric login failed. Use your password.'; msg.classList.remove('hidden'); btn.disabled = false; }
            } catch (e) { msg.textContent = 'Biometric login was cancelled.'; msg.classList.remove('hidden'); btn.disabled = false; }
        });
    })();

    (function () {
        try {
            var emailField = document.getElementById('email');
            var pwForm = document.querySelector('form[action="{{ route('login') }}"]');
            if (pwForm) pwForm.addEventListener('submit', function () {
                if (emailField && emailField.value) localStorage.setItem('gc_last_email', emailField.value.trim());
            });
            var saved = localStorage.getItem('gc_last_email');
            if (saved) {
                if (emailField && !emailField.value) emailField.value = saved;
                var pinWrap = document.getElementById('pin-login-wrap');
                var pinEmail = document.getElementById('pin-login-email');
                var pinName = document.getElementById('pin-login-name');
                if (pinWrap && pinEmail) {
                    pinEmail.value = saved;
                    if (pinName) pinName.textContent = saved;
                    pinWrap.classList.remove('hidden');
                }
                if (emailField && pinEmail) emailField.addEventListener('input', function () { pinEmail.value = emailField.value.trim(); });
            }
        } catch (e) {}
    })();
</script>
</body>
</html>
