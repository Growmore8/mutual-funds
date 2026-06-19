<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Log in · {{ \App\Models\Setting::get('app_name', 'GrowthCapital') }}</title>
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/logo.png">
    <script>(function(){try{var t=localStorage.getItem('theme');if(t==='dark'||(!t&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark');}}catch(e){}})();</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-100 text-gray-800">
<div class="min-h-full lg:grid lg:grid-cols-2">

    {{-- Brand panel --}}
    <div class="hidden lg:flex flex-col justify-between bg-[#0a1730] text-white p-12 relative overflow-hidden">
        <div class="relative z-10 flex items-center gap-3">
            <img src="/logo.png" alt="GrowthCapital" class="w-12 h-12" onerror="this.style.display='none'">
            <div>
                <div class="text-2xl font-bold">{{ \App\Models\Setting::get('app_name', 'GrowthCapital') }}</div>
                <p class="text-xs text-gray-400">Mutual Funds</p>
            </div>
        </div>
        <div class="relative z-10 max-w-md">
            <h1 class="text-3xl font-bold leading-tight">Invest together.<br>Earn together.</h1>
            <p class="text-gray-300 mt-4 text-sm leading-relaxed">Your capital joins our professionally managed pool. Profit is distributed daily, in proportion to your share — and your funds always remain under your ownership.</p>
            <div class="flex gap-6 mt-8 text-sm">
                <div><p class="text-2xl font-bold text-emerald-400"><i class="fa-solid fa-shield-halved"></i></p><p class="text-gray-400 mt-1">Secured &amp; verified</p></div>
                <div><p class="text-2xl font-bold text-emerald-400"><i class="fa-solid fa-chart-line"></i></p><p class="text-gray-400 mt-1">Daily profit share</p></div>
            </div>
        </div>
        <p class="relative z-10 text-xs text-gray-500">&copy; {{ date('Y') }} GrowthCapital Ltd · License 11064258</p>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 rounded-full bg-emerald-500/10"></div>
        <div class="absolute -top-20 -left-20 w-72 h-72 rounded-full bg-emerald-500/10"></div>
    </div>

    {{-- Form panel --}}
    <div class="flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-md">
            {{-- mobile brand --}}
            <div class="lg:hidden text-center mb-8">
                <img src="/logo.png" alt="GrowthCapital" class="w-16 h-16 mx-auto mb-2" onerror="this.style.display='none'">
                <div class="text-2xl font-bold text-[#0a1730] dark:text-white">{{ \App\Models\Setting::get('app_name', 'GrowthCapital') }}</div>
                <p class="text-xs text-gray-400">Mutual Funds</p>
            </div>

            <div class="bg-white shadow-xl rounded-2xl p-8">
                <h2 class="text-2xl font-bold text-gray-900">Welcome back</h2>
                <p class="text-sm text-gray-500 mt-1 mb-6">Log in to your account to continue.</p>

                @if (session('status'))
                    <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <div class="relative">
                            <i class="fa-solid fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                                   class="w-full pl-10 border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="you@example.com">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative" x-data="{ show: false }">
                            <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input id="password" :type="show ? 'text' : 'password'" name="password" required autocomplete="current-password"
                                   class="w-full pl-10 pr-10 border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="••••••••">
                            <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label for="remember_me" class="inline-flex items-center">
                            <input id="remember_me" type="checkbox" name="remember" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="ms-2 text-sm text-gray-600">Remember me</span>
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">Forgot password?</a>
                        @endif
                    </div>

                    <button class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 rounded-lg transition">
                        <i class="fa-solid fa-right-to-bracket mr-1"></i> Log in
                    </button>
                </form>

                {{-- Fast biometric login (shown only if this device supports it) --}}
                <div id="bio-login-wrap" class="hidden mt-4">
                    <div class="relative my-4 text-center"><span class="text-xs text-gray-400 bg-white dark:bg-[#0f1b38] px-2 relative z-10">or</span><div class="absolute inset-x-0 top-1/2 border-t border-gray-200 dark:border-white/10"></div></div>
                    <button type="button" id="bio-login-btn" class="w-full border border-emerald-500 text-emerald-600 dark:text-emerald-300 font-semibold py-2.5 rounded-lg flex items-center justify-center gap-2 hover:bg-emerald-50 dark:hover:bg-emerald-500/10">
                        <i class="fa-solid fa-fingerprint"></i> Login with Face ID / fingerprint
                    </button>
                    <p id="bio-login-msg" class="text-xs text-center text-red-600 mt-2 hidden"></p>
                </div>

                @if (Route::has('register'))
                    <p class="text-center text-sm text-gray-500 mt-6">
                        Don't have an account?
                        <a href="{{ route('register') }}" class="text-emerald-600 hover:text-emerald-700 font-semibold">Create one</a>
                    </p>
                @endif
            </div>

            <p class="text-center text-xs text-gray-400 mt-6">Protected by email verification &amp; KYC. <i class="fa-solid fa-shield-halved"></i></p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/@laragear/webpass@2/dist/webpass.js"></script>
<script>
    (function () {
        var wrap = document.getElementById('bio-login-wrap');
        var btn = document.getElementById('bio-login-btn');
        var msg = document.getElementById('bio-login-msg');
        if (!wrap || typeof Webpass === 'undefined' || (Webpass.isUnsupported && Webpass.isUnsupported())) return;

        // Show the option only when a platform authenticator (Face ID / fingerprint) exists.
        if (window.PublicKeyCredential && PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable) {
            PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().then(function (ok) {
                if (ok) wrap.classList.remove('hidden');
            });
        }

        btn.addEventListener('click', async function () {
            msg.classList.add('hidden');
            btn.disabled = true;
            try {
                const email = (document.getElementById('email') || {}).value || '';
                const { success, data } = await Webpass.assert(
                    { path: '{{ route('webauthn.login.options') }}', body: email ? { email } : {} },
                    '{{ route('webauthn.login') }}'
                );
                if (success) {
                    window.location.href = (data && data.redirect) ? data.redirect : '{{ route('dashboard') }}';
                } else {
                    msg.textContent = 'Biometric login failed. Use your password.';
                    msg.classList.remove('hidden'); btn.disabled = false;
                }
            } catch (e) {
                msg.textContent = 'Biometric login was cancelled.';
                msg.classList.remove('hidden'); btn.disabled = false;
            }
        });
    })();
</script>
</body>
</html>
