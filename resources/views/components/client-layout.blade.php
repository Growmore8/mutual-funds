@props(['title' => 'Dashboard'])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · GrowthCapital Funds</title>
    <script>(function(){try{var t=localStorage.getItem('theme');if(t!=='light'){document.documentElement.classList.add('dark');}}catch(e){document.documentElement.classList.add('dark');}})();</script>
    {{-- PWA --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0a1730">
    <link rel="apple-touch-icon" href="/logo.png">
    <link rel="icon" href="/logo.png" type="image/png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="GC Fund">
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
    </style>
</head>
<body class="app-shell bg-gray-50 text-gray-800 dark:bg-[#070d1f] dark:text-gray-200 overflow-hidden flex flex-col lg:overflow-visible lg:block" x-data="{ sheet: false }">
<div class="flex-1 min-h-0 overflow-y-auto lg:flex-none lg:min-h-[100dvh] lg:overflow-visible lg:flex">

    {{-- Desktop sidebar --}}
    <aside class="hidden lg:flex lg:flex-col w-64 bg-[#0a1730] text-gray-300 fixed inset-y-0">
        <div class="px-6 h-16 shrink-0 border-b border-white/[0.06] flex items-center gap-2.5">
            <img src="/logo.png" alt="" class="w-9 h-9 shrink-0" onerror="this.style.display='none'">
            <div class="text-white font-bold text-lg leading-tight">Growth<span class="text-emerald-400">Capital</span><span class="block text-xs font-normal text-gray-400">Mutual Funds</span></div>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1 text-sm overflow-y-auto">
            @php
                $link = fn ($active) => 'nav-link flex items-center gap-3 px-3 py-2 rounded-lg ' . ($active ? 'bg-emerald-500 text-[#04231a] font-semibold shadow' : 'hover:bg-white/10');
                $head = '<p class="px-3 pt-4 pb-1 text-[10px] font-semibold uppercase tracking-wider text-gray-500">';
            @endphp
            <a href="{{ route('client.dashboard') }}" class="{{ $link(request()->routeIs('client.dashboard')) }}"><i class="fa-solid fa-gauge-high w-5 text-center"></i> Dashboard</a>

            {!! $head !!}Money</p>
            <a href="{{ route('client.deposit.create') }}" class="{{ $link(request()->routeIs('client.deposit.*')) }}"><i class="fa-solid fa-arrow-down w-5 text-center"></i> Deposit</a>
            <a href="{{ route('withdraw.create') }}" class="{{ $link(request()->routeIs('withdraw.*')) }}"><i class="fa-solid fa-money-bill-transfer w-5 text-center"></i> Withdraw</a>

            {!! $head !!}Activity</p>
            <a href="{{ route('client.profit') }}" class="{{ $link(request()->routeIs('client.profit')) }}"><i class="fa-solid fa-chart-line w-5 text-center"></i> Profit History</a>
            <a href="{{ route('client.transactions') }}" class="{{ $link(request()->routeIs('client.transactions')) }}"><i class="fa-solid fa-receipt w-5 text-center"></i> Transactions</a>
            <a href="{{ route('accounts.index') }}" class="{{ $link(request()->routeIs('accounts.*')) }}"><i class="fa-solid fa-layer-group w-5 text-center"></i> My Account</a>
            <x-statement-modal :base-url="route('client.statement')" class="nav-link w-full text-left flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10"><i class="fa-solid fa-file-pdf w-5 text-center"></i> Statement</x-statement-modal>

            {!! $head !!}Account</p>
            <a href="{{ route('security.index') }}" class="{{ $link(request()->routeIs('security.*')) }}"><i class="fa-solid fa-shield-halved w-5 text-center"></i> Security</a>
            <button type="button" onclick="var d=document.documentElement.classList.toggle('dark');localStorage.setItem('theme',d?'dark':'light');"
                    class="nav-link w-full text-left flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10">
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
        <form method="POST" action="{{ route('logout') }}" class="px-3 pt-3 border-t border-white/10">@csrf
            <button class="w-full text-left flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-red-300"><i class="fa-solid fa-right-from-bracket w-5 text-center"></i> Log out</button>
        </form>
        <div class="text-[11px] text-gray-500 px-4 pb-4 pt-1">
            <p class="font-semibold text-gray-300">GrowthCapital Ltd.</p>
            <p>© {{ date('Y') }} All rights reserved.</p>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 lg:pl-64">
        {{-- Top bar: logo+name left, notifications + profile right --}}
        <header class="bg-white/95 border-b border-gray-200 dark:bg-[#0a1730]/80 dark:border-white/[0.06] backdrop-blur sticky top-0 z-30 safe-t">
            <div class="px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0 lg:hidden">
                    <img src="/logo.png" alt="" class="w-8 h-8 shrink-0" onerror="this.style.display='none'">
                    <span class="font-bold text-[#0a1730] dark:text-white truncate">Growth<span class="text-emerald-500">Capital</span></span>
                </div>
                <div class="hidden lg:block"></div>
                <x-notification-bell />
            </div>
        </header>

        <main class="px-4 sm:px-6 lg:px-8 py-6 page-in">
            @if (session('status'))
                <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3">{{ session('status') }}</div>
            @endif
            {{ $slot }}
        </main>
    </div>
</div>{{-- end app wrapper --}}

    {{-- Deposit/Withdraw action sheet (mobile +) --}}
    <div x-show="sheet" x-transition.opacity @click="sheet=false" style="display:none" class="lg:hidden fixed inset-0 bg-black/40 z-40"></div>
    <div x-show="sheet" x-transition x-cloak class="lg:hidden fixed inset-x-0 bottom-24 z-50 px-6 safe-b">
        <div class="bg-white dark:bg-[#0f1b38] dark:ring-1 dark:ring-white/10 rounded-2xl shadow-xl p-3 max-w-sm mx-auto grid grid-cols-2 gap-3">
            <a href="{{ route('client.deposit.create') }}" class="flex flex-col items-center gap-1 py-4 rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                <i class="fa-solid fa-arrow-down text-xl"></i><span class="text-sm font-medium">Deposit</span>
            </a>
            <a href="{{ route('withdraw.create') }}" class="flex flex-col items-center gap-1 py-4 rounded-xl bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                <i class="fa-solid fa-money-bill-transfer text-xl"></i><span class="text-sm font-medium">Withdraw</span>
            </a>
        </div>
    </div>

    {{-- Mobile bottom nav: active item expands into a labeled pill (ref style) --}}
    @php
        $navLinks = [
            ['route' => 'client.dashboard',    'match' => 'client.dashboard',    'icon' => 'fa-house',                 'label' => 'Home'],
            ['route' => 'client.profit',       'match' => 'client.profit',       'icon' => 'fa-chart-pie',             'label' => 'History'],
            ['route' => 'client.transactions', 'match' => 'client.transactions', 'icon' => 'fa-arrow-right-arrow-left','label' => 'Transactions'],
            ['route' => 'profile.edit',        'match' => 'profile.edit',        'icon' => 'fa-user',                  'label' => 'Profile'],
        ];
    @endphp
    <nav class="lg:hidden shrink-0 relative z-10 px-2" style="padding-top:0.2rem;padding-bottom:max(0.15rem,calc(env(safe-area-inset-bottom) - 34px))">
        <div class="flex items-center justify-around gap-1">
            @foreach ($navLinks as $i => $it)
                @php $active = request()->routeIs($it['match']); @endphp
                <a href="{{ route($it['route']) }}"
                   class="flex items-center justify-center rounded-full transition-all duration-200 {{ $active ? 'gap-2 px-4 h-12 bg-emerald-500/15 text-emerald-400' : 'w-12 h-12 text-gray-400 hover:text-gray-200' }}">
                    <i class="fa-solid {{ $it['icon'] }} text-lg"></i>
                    @if ($active)<span class="text-sm font-semibold whitespace-nowrap">{{ $it['label'] }}</span>@endif
                </a>

                @if ($i === 1)
                    {{-- center action (+) --}}
                    <button type="button" @click="sheet=!sheet" class="w-12 h-12 shrink-0 rounded-full bg-emerald-500 text-white grid place-items-center active:scale-95 transition">
                        <i class="fa-solid fa-plus text-lg" :class="sheet ? 'rotate-45' : ''" style="transition:transform .2s"></i>
                    </button>
                @endif
            @endforeach
        </div>
    </nav>

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); });
    }
</script>
</body>
</html>
