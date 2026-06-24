@props(['title' => 'Dashboard', 'embed' => false])
@php
    $appName = \App\Models\Setting::get('app_name', 'GrowthCapital');
    $appShort = \App\Models\Setting::get('app_short_name', 'GC Fund');
    $brandV = \App\Models\Setting::get('brand_v', '1');
    $favicon = \App\Models\Setting::get('favicon_path', '/logo.png');
    $appIcon = \App\Models\Setting::get('app_icon_path', '/logo.png');
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-key" content="{{ config('services.webpush.public_key') }}">
    <title>{{ $title }} · {{ $appName }}</title>
    <script>(function(){try{var t=localStorage.getItem('theme');if(t!=='light'){document.documentElement.classList.add('dark');}}catch(e){document.documentElement.classList.add('dark');}})();</script>
    {{-- Decide the loading screen BEFORE paint so the app/nav never flashes first --}}
    <script>(function(){try{if(!sessionStorage.getItem('gcAppLive')){sessionStorage.setItem('gcAppLive','1');document.documentElement.classList.add('gc-splash');}}catch(e){}})();</script>
    {{-- PWA --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#070b16">
    <link rel="apple-touch-icon" href="{{ $appIcon }}?v={{ $brandV }}">
    <link rel="icon" href="{{ $favicon }}?v={{ $brandV }}" type="image/png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $appShort }}">
    {{-- iOS launch screens: dark splash w/ logo (stops iOS drawing an icon square on cold launch) --}}
    @php
        $iosDevices = [
            [430, 932, 3], [428, 926, 3], [414, 896, 3], [414, 896, 2], [393, 852, 3], [390, 844, 3],
            [375, 812, 3], [414, 736, 3], [375, 667, 2], [320, 568, 2],
        ];
    @endphp
    @foreach ($iosDevices as [$dw, $dh, $ratio])
        <link rel="apple-touch-startup-image"
              media="(device-width: {{ $dw }}px) and (device-height: {{ $dh }}px) and (-webkit-device-pixel-ratio: {{ $ratio }}) and (orientation: portrait)"
              href="/apple-splash?w={{ $dw * $ratio }}&h={{ $dh * $ratio }}&v={{ $brandV }}">
    @endforeach
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .nav-link{transition:all .15s ease}
        .nav-link:hover{transform:translateX(2px)}
        .tab{position:relative;transition:color .15s ease}
        .tab .tab-ico{padding:.3rem 1.05rem;border-radius:9999px;transition:background .2s ease, color .2s ease, transform .2s ease}
        .tab.is-active{color:#10b981;font-weight:600}
        .tab.is-active .tab-ico{background:rgba(16,185,129,.16);color:#10b981;transform:translateY(-1px)}
        .tab.is-active::before{content:"";position:absolute;top:0;left:50%;transform:translateX(-50%);width:26px;height:3px;border-radius:0 0 3px 3px;background:#10b981}
        .safe-b{padding-bottom:env(safe-area-inset-bottom)}
        .safe-t{padding-top:env(safe-area-inset-top)}
        /* Premium dark backdrop: near-black with a soft emerald glow up top */
        html.dark body{
            background-color:#070b16;
            background-image:radial-gradient(1100px 520px at 50% -12%, rgba(16,185,129,.12), transparent 60%);
            background-attachment:fixed;
        }
        html.dark .gcard,html.dark aside{background-color:rgba(255,255,255,.035)!important}
        html.dark aside{background-color:#0a1228!important}
        /* Smooth page-in transition on every navigation */
        @keyframes pagein{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
        .page-in{animation:pagein .32s cubic-bezier(.22,1,.36,1) both}
        @media (prefers-reduced-motion: reduce){.page-in{animation:none}}
        /* App shell height: 100vh fallback, 100dvh where supported (fixes nav drift on webviews without dvh) */
        .app-shell{height:100vh;height:100dvh}
        @media (min-width:1024px){.app-shell{height:auto;min-height:100vh;min-height:100dvh}}
        /* Gooey bead-chain bottom nav */
        .goo{filter:url(#goofilter)}
        .bead{width:58px;height:58px;border-radius:9999px;background:#0d1834;flex:0 0 auto}
        html:not(.dark) .bead{background:#0f1b38}
        .bead + .bead{margin-left:-14px}
        .beadcell{width:58px;height:58px;flex:0 0 auto;display:grid;place-items:center}
        .beadcell + .beadcell{margin-left:-14px}
        /* Loading screen: hidden by default, shown only when html.gc-splash is set in <head> */
        #gc-splash{display:none}
        html.gc-splash #gc-splash{display:flex}
        html.gc-splash{overflow:hidden}
        @keyframes gcLogoIn{0%{transform:scale(.7);opacity:0}60%{transform:scale(1.05);opacity:1}100%{transform:scale(1)}}
        @keyframes gcGlow{0%,100%{box-shadow:0 0 40px rgba(16,185,129,.25)}50%{box-shadow:0 0 70px rgba(16,185,129,.55)}}
        @keyframes gcBar{0%{transform:translateX(-120%)}100%{transform:translateX(320%)}}
        @keyframes gcFade{0%{opacity:0;transform:translateY(8px)}100%{opacity:1;transform:translateY(0)}}
        .gc-logo-box{animation:gcLogoIn .6s cubic-bezier(.2,.8,.2,1) both, gcGlow 2.2s ease-in-out 0.6s infinite}
        .gc-splash-fade{animation:gcFade .6s ease .25s both}
        .gc-bar-track{width:180px;height:4px;border-radius:9999px;background:rgba(255,255,255,.08);overflow:hidden}
        .gc-bar-track > i{display:block;width:40%;height:100%;border-radius:9999px;background:linear-gradient(90deg,transparent,#10b981,#34d399);animation:gcBar 1.1s ease-in-out infinite}
    </style>
</head>
<body class="min-h-screen bg-gray-50 text-gray-800 dark:bg-[#070d1f] dark:text-gray-200" x-data="{ sheet: false }">

{{-- Loading screen (painted before the app; covers nav so there's no glitch) --}}
@unless ($embed)
<div id="gc-splash" class="fixed inset-0 z-[200] flex-col items-center justify-center" style="background:radial-gradient(1000px 600px at 50% 18%,rgba(16,185,129,.16),transparent 60%),linear-gradient(165deg,#0a1f1b 0%,#070b16 55%)">
    <div class="text-center px-8">
        <div class="gc-logo-box mx-auto w-24 h-24 rounded-[26px] grid place-items-center bg-white/[0.06] ring-1 ring-emerald-400/30">
            <img src="/logo.png?v={{ $brandV }}" alt="" class="w-14 h-14" onerror="this.style.display='none'">
        </div>
        <div class="gc-splash-fade">
            <p class="mt-6 text-3xl font-extrabold text-white tracking-wide">{!! preg_replace('/(capital)/i', '<span class="text-emerald-400">$1</span>', e($appName)) !!}</p>
            <p class="text-[11px] tracking-[0.4em] uppercase text-gray-400 mt-2">Mutual Fund</p>
        </div>
    </div>
    <div class="gc-bar-track mt-9"><i></i></div>
    <p class="absolute bottom-8 text-[11px] text-gray-500 tracking-wide">{{ \App\Models\Setting::get('app_slogan', 'Invest together · Earn together') }}</p>
</div>
<script>
    (function () {
        var el = document.getElementById('gc-splash'); if (!el) return;
        function hide() { el.style.transition = 'opacity .5s'; el.style.opacity = '0'; setTimeout(function () { document.documentElement.classList.remove('gc-splash'); el.style.opacity = ''; el.style.transition = ''; }, 520); }
        function show() { document.documentElement.classList.add('gc-splash'); setTimeout(hide, 3000); }
        if (document.documentElement.classList.contains('gc-splash')) { setTimeout(hide, 3000); }
        var hiddenAt = 0;
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) { hiddenAt = Date.now(); }
            else if (hiddenAt && (Date.now() - hiddenAt) > 3000) { show(); }
        });
    })();
</script>
@endunless
<div class="{{ $embed ? '' : 'lg:flex lg:min-h-screen' }}">

    {{-- Desktop sidebar --}}
    @unless ($embed)
    <aside class="hidden lg:flex lg:flex-col w-64 bg-[#0a1730] text-gray-300 fixed inset-y-0">
        <div class="px-6 h-16 shrink-0 border-b border-white/[0.06] flex items-center gap-2.5">
            <img src="/logo.png?v={{ $brandV }}" alt="" class="w-9 h-9 shrink-0" onerror="this.style.display='none'">
            <div class="text-white font-bold text-lg leading-tight">{{ $appName }}<span class="block text-xs font-normal text-gray-400">Mutual Funds</span></div>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1 text-sm overflow-y-auto">
            @php
                $link = fn ($active) => 'nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition ' . ($active ? 'bg-white/[0.08] text-white font-semibold ring-1 ring-white/10' : 'text-gray-400 hover:bg-white/[0.05] hover:text-white');
                $head = '<p class="px-3 pt-5 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-gray-500">';
            @endphp
            <a href="{{ route('client.dashboard') }}" class="{{ $link(request()->routeIs('client.dashboard')) }}"><i class="fa-solid fa-gauge-high w-5 text-center"></i> Dashboard</a>
            <a href="{{ route('markets.index') }}" class="{{ $link(request()->routeIs('markets.*')) }}"><i class="fa-solid fa-chart-simple w-5 text-center"></i> Markets</a>

            {!! $head !!}Spot Trading</p>
            <a href="{{ route('spot.index') }}" class="{{ $link(request()->routeIs('spot.*')) }}"><i class="fa-solid fa-arrow-trend-up w-5 text-center"></i> Spot Trading</a>

            {!! $head !!}Money</p>
            <a href="{{ route('client.deposit.create') }}" class="{{ $link(request()->routeIs('client.deposit.*')) }}"><i class="fa-solid fa-arrow-down w-5 text-center"></i> Deposit</a>
            <a href="{{ route('withdraw.create') }}" class="{{ $link(request()->routeIs('withdraw.*')) }}"><i class="fa-solid fa-money-bill-transfer w-5 text-center"></i> Withdraw</a>
            <a href="{{ route('payout.index') }}" class="{{ $link(request()->routeIs('payout.*')) }}"><i class="fa-solid fa-money-check-dollar w-5 text-center"></i> Withdrawal Methods</a>

            {!! $head !!}Activity</p>
            <a href="{{ route('client.profit') }}" class="{{ $link(request()->routeIs('client.profit')) }}"><i class="fa-solid fa-chart-line w-5 text-center"></i> Profit History</a>
            <a href="{{ route('client.transactions') }}" class="{{ $link(request()->routeIs('client.transactions')) }}"><i class="fa-solid fa-receipt w-5 text-center"></i> Transactions</a>
            <a href="{{ route('accounts.index') }}" class="{{ $link(request()->routeIs('accounts.*')) }}"><i class="fa-solid fa-layer-group w-5 text-center"></i> My Account</a>
            <a href="{{ route('client.referrals') }}" class="{{ $link(request()->routeIs('client.referrals')) }}"><i class="fa-solid fa-gift w-5 text-center"></i> Refer &amp; Earn</a>
            <x-statement-modal :base-url="route('client.statement')" class="nav-link w-full text-left flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-400 hover:bg-white/[0.05] hover:text-white transition"><i class="fa-solid fa-file-pdf w-5 text-center"></i> Statement</x-statement-modal>

            {!! $head !!}Account</p>
            <a href="{{ route('profile.edit') }}" class="{{ $link(request()->routeIs('profile.edit')) }}"><i class="fa-solid fa-user w-5 text-center"></i> Profile</a>
            <a href="{{ route('security.index') }}" class="{{ $link(request()->routeIs('security.*')) }}"><i class="fa-solid fa-shield-halved w-5 text-center"></i> Security</a>
            <button type="button" onclick="var d=document.documentElement.classList.toggle('dark');localStorage.setItem('theme',d?'dark':'light');"
                    class="nav-link w-full text-left flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-400 hover:bg-white/[0.05] hover:text-white transition">
                <i class="fa-solid fa-circle-half-stroke w-5 text-center"></i> Appearance
            </button>
        </nav>
        {{-- Need Help card --}}
        <div class="px-3 pb-2">
            <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                <p class="font-semibold text-white text-sm">Need Help?</p>
                <p class="text-xs text-gray-400 mt-1">Our support team is here to help you.</p>
                <a href="{{ route('support.index') }}" class="mt-3 flex items-center justify-center gap-2 px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold"><i class="fa-solid fa-headset"></i> Contact Support</a>
            </div>
        </div>
        {{-- User profile card + logout --}}
        <div class="p-3 border-t border-white/[0.06]">
            <div class="flex items-center gap-3 rounded-xl bg-white/[0.04] ring-1 ring-white/10 p-2.5">
                <div class="w-9 h-9 rounded-full bg-emerald-500 text-[#04231a] grid place-items-center font-bold shrink-0">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-gray-400 truncate font-mono">{{ auth()->user()->clientCode() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button title="Log out" class="w-8 h-8 grid place-items-center rounded-lg text-gray-400 hover:text-red-300 hover:bg-white/10"><i class="fa-solid fa-right-from-bracket"></i></button>
                </form>
            </div>
            <p class="text-[10px] text-gray-500 mt-2 px-1">GrowthCapital Ltd. · © {{ date('Y') }}</p>
        </div>
    </aside>
    @endunless

    {{-- Main --}}
    <div class="{{ $embed ? '' : 'flex-1 lg:pl-64' }}">
        {{-- Top bar: logo+name left, notifications + profile right --}}
        @unless ($embed)
        <header class="bg-white/95 border-b border-gray-200 dark:bg-[#0a1730]/80 dark:border-white/[0.06] backdrop-blur sticky top-0 z-30 safe-t">
            <div class="px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0 lg:hidden">
                    <img src="/logo.png?v={{ $brandV }}" alt="" class="w-8 h-8 shrink-0" onerror="this.style.display='none'">
                    <span class="font-bold text-[#0a1730] dark:text-white truncate">{{ $appName }}</span>
                </div>
                <div class="hidden lg:block"></div>
                <div class="flex items-center gap-2">
                    {{-- Desktop account switcher (only when the client has more than one account) --}}
                    @php $hdrAccs = auth()->user()->fundAccounts; $hdrCur = auth()->user()->currentAccount(); @endphp
                    @if ($hdrAccs->count() > 1)
                        <div class="relative" x-data="{ o:false }">
                            <button type="button" @click="o=!o" class="flex items-center gap-2 px-3 py-1.5 rounded-xl border border-gray-200 dark:border-white/10 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5">
                                <i class="fa-solid fa-layer-group text-emerald-500"></i>
                                <span class="font-medium max-w-[7rem] sm:max-w-[10rem] truncate">{{ $hdrCur->label ?? 'Account' }}</span>
                                <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                            </button>
                            <div x-show="o" @click.outside="o=false" x-transition style="display:none" class="absolute right-0 mt-2 w-72 bg-white dark:bg-[#0a1730] border border-gray-200 dark:border-white/10 rounded-xl shadow-xl p-1.5 z-50">
                                <p class="px-3 py-1.5 text-[10px] uppercase tracking-wider text-gray-400">Switch account</p>
                                @foreach ($hdrAccs as $a)
                                    <form method="POST" action="{{ route('accounts.switch', $a) }}">@csrf
                                        <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-left {{ $hdrCur && $hdrCur->id===$a->id ? 'bg-emerald-50 dark:bg-emerald-500/10' : 'hover:bg-gray-50 dark:hover:bg-white/5' }}">
                                            <span class="flex-1 min-w-0">
                                                <span class="block text-sm font-medium text-gray-900 dark:text-white truncate">{{ $a->label }}</span>
                                                <span class="block text-xs text-gray-400 truncate">{{ $a->accountType->name ?? 'No plan' }} · {{ $a->code() }}</span>
                                            </span>
                                            @if($hdrCur && $hdrCur->id===$a->id)<i class="fa-solid fa-check text-emerald-500"></i>@endif
                                        </button>
                                    </form>
                                @endforeach
                                <a href="{{ route('accounts.index') }}" class="block px-3 py-2 text-sm text-emerald-600 dark:text-emerald-400 hover:bg-gray-50 dark:hover:bg-white/5 rounded-lg"><i class="fa-solid fa-circle-plus mr-1"></i> Open another account</a>
                            </div>
                        </div>
                    @endif
                    <x-notification-bell />
                </div>
            </div>
        </header>
        @endunless

        <main class="{{ $embed ? 'px-4 pt-4 pb-6' : 'px-4 sm:px-6 lg:px-8 pt-6 pb-28 lg:pb-8' }} page-in">
            @if (auth()->user()?->status === 'locked')
                <div class="mb-5 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg p-3 flex items-start gap-2">
                    <i class="fa-solid fa-lock mt-0.5"></i>
                    <span>Your account is currently <strong>view-only</strong> while under review. Deposits, withdrawals and statement export are disabled. Please contact support.</span>
                </div>
            @endif
            @if (session('status'))
                <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3">{{ session('status') }}</div>
            @endif
            @if ($errors->has('locked'))
                <div class="mb-5 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg p-3">{{ $errors->first('locked') }}</div>
            @endif
            {{ $slot }}
        </main>
    </div>
</div>{{-- end app wrapper --}}

    @unless ($embed)
    {{-- Global bottom-sheet (mobile): slides up with Deposit/Withdraw/Transfer in an embedded view --}}
    <div x-show="$store.sheet.open" x-cloak class="lg:hidden fixed inset-0 z-[80]">
        <div class="absolute inset-0 bg-black/50" @click="$store.sheet.close()" x-transition.opacity></div>
        <div class="absolute inset-x-0 bottom-0 h-[92vh] bg-white dark:bg-[#0a1326] rounded-t-2xl shadow-2xl flex flex-col overflow-hidden"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-white/10 shrink-0">
                <span class="font-semibold text-gray-900 dark:text-white" x-text="$store.sheet.title"></span>
                <button @click="$store.sheet.close()" class="w-9 h-9 grid place-items-center rounded-full bg-gray-100 dark:bg-white/10 text-gray-500"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <iframe :src="$store.sheet.url" class="flex-1 w-full border-0 bg-white dark:bg-[#070d1f]"></iframe>
        </div>
    </div>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('sheet', {
                open: false, url: '', title: '',
                show(u, t) {
                    if (window.innerWidth >= 1024) { window.location = u; return; }
                    this.url = u + (u.includes('?') ? '&' : '?') + 'embed=1';
                    this.title = t; this.open = true;
                },
                close() { this.open = false; setTimeout(() => { this.url = ''; }, 250); },
            });
        });
    </script>

    {{-- Mobile bottom nav: active item expands into a labeled pill (ref style) --}}
    @php
        $navLinks = [
            ['route' => 'client.dashboard',    'match' => 'client.dashboard',    'icon' => 'fa-house',                 'label' => 'Home'],
            ['route' => 'markets.index',       'match' => 'markets.*',            'icon' => 'fa-chart-simple',          'label' => 'Markets'],
            ['route' => 'spot.index',          'match' => 'spot.*',              'icon' => 'fa-arrow-trend-up',        'label' => 'Spot'],
            ['route' => 'client.transactions', 'match' => 'client.transactions', 'icon' => 'fa-arrow-right-arrow-left','label' => 'Transactions'],
            ['route' => 'profile.edit',        'match' => 'profile.edit',        'icon' => 'fa-user',                  'label' => 'Profile'],
        ];
    @endphp
    <nav class="lg:hidden fixed inset-x-0 bottom-0 z-40 bg-white dark:bg-[#0a1326] border-t border-gray-200 dark:border-white/[0.06] px-1 pt-2"
         style="padding-bottom:max(0.45rem,env(safe-area-inset-bottom))">
        <div class="flex items-center justify-around">
            @foreach ($navLinks as $i => $it)
                @php $active = request()->routeIs($it['match']); @endphp
                <a href="{{ route($it['route']) }}"
                   class="flex flex-col items-center gap-1 w-16 py-0.5 transition {{ $active ? 'text-emerald-500' : 'text-gray-400 hover:text-emerald-400' }}">
                    <i class="fa-solid {{ $it['icon'] }} text-[19px]"></i>
                    <span class="text-[10px] font-medium leading-none">{{ $it['label'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>
    @endunless

{{-- "Update available" banner (shown when a new app version is ready) --}}
<div id="sw-update-banner" class="hidden fixed inset-x-0 bottom-24 lg:bottom-4 z-[60] px-4">
    <div class="mx-auto max-w-md flex items-center gap-3 bg-emerald-600 text-white rounded-2xl shadow-xl px-4 py-3">
        <i class="fa-solid fa-rotate"></i>
        <div class="flex-1 text-sm font-medium">A new version is available.</div>
        <button id="sw-update-btn" class="bg-white text-emerald-700 font-semibold text-sm px-3 py-1.5 rounded-lg">Update</button>
    </div>
</div>

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').then(function (reg) {
                function showBanner(worker) {
                    var b = document.getElementById('sw-update-banner');
                    var btn = document.getElementById('sw-update-btn');
                    if (!b || !btn) return;
                    b.classList.remove('hidden');
                    btn.onclick = function () {
                        btn.disabled = true; btn.textContent = 'Updating…';
                        worker.postMessage('SKIP_WAITING');
                    };
                }
                // A new version is already installed and waiting.
                if (reg.waiting && navigator.serviceWorker.controller) showBanner(reg.waiting);
                // A new version finishes installing while the app is open.
                reg.addEventListener('updatefound', function () {
                    var nw = reg.installing;
                    if (!nw) return;
                    nw.addEventListener('statechange', function () {
                        if (nw.state === 'installed' && navigator.serviceWorker.controller) showBanner(nw);
                    });
                });
                // Check for updates whenever the app regains focus.
                document.addEventListener('visibilitychange', function () { if (!document.hidden) reg.update().catch(function () {}); });
            }).catch(function () {});

            var reloaded = false;
            navigator.serviceWorker.addEventListener('controllerchange', function () {
                if (reloaded) return; reloaded = true; window.location.reload();
            });
        });
    }
</script>
</body>
</html>
