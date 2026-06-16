@props(['title' => 'Dashboard'])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · GrowthCapital Funds</title>
    <script>(function(){try{var t=localStorage.getItem('theme');if(t==='dark'||(!t&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark');}}catch(e){}})();</script>
    {{-- PWA --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0a1730">
    <link rel="apple-touch-icon" href="/logo.png">
    <link rel="icon" href="/logo.png" type="image/png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="GrowthCapital">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .nav-link{transition:all .15s ease}
        .nav-link:hover{transform:translateX(2px)}
        .tab{transition:transform .15s ease, color .15s ease}
        .tab.is-active{color:#059669}
        .tab.is-active .tab-ico{transform:translateY(-2px) scale(1.08)}
        .safe-b{padding-bottom:env(safe-area-inset-bottom)}
        .safe-t{padding-top:env(safe-area-inset-top)}
    </style>
</head>
<body class="h-full bg-gray-50 text-gray-800" x-data="{ sheet: false }">
<div class="min-h-full lg:flex">

    {{-- Desktop sidebar --}}
    <aside class="hidden lg:flex lg:flex-col w-64 bg-[#0a1730] text-gray-300 fixed inset-y-0">
        <div class="px-6 py-5 border-b border-white/10 flex items-center gap-2.5">
            <img src="/logo.png" alt="" class="w-9 h-9 shrink-0" onerror="this.style.display='none'">
            <div class="text-white font-bold text-lg leading-tight">Growth<span class="text-emerald-400">Capital</span><span class="block text-xs font-normal text-gray-400">Mutual Funds</span></div>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
            @php $link = fn ($active) => 'nav-link flex items-center gap-3 px-3 py-2 rounded-lg ' . ($active ? 'bg-emerald-500 text-[#04231a] font-semibold shadow' : 'hover:bg-white/10'); @endphp
            <a href="{{ route('client.dashboard') }}" class="{{ $link(request()->routeIs('client.dashboard')) }}"><i class="fa-solid fa-gauge-high w-5 text-center"></i> Dashboard</a>
            <a href="{{ route('client.profit') }}" class="{{ $link(request()->routeIs('client.profit')) }}"><i class="fa-solid fa-chart-line w-5 text-center"></i> Profit History</a>
            <a href="{{ route('client.transactions') }}" class="{{ $link(request()->routeIs('client.transactions')) }}"><i class="fa-solid fa-receipt w-5 text-center"></i> Transactions</a>
            <a href="{{ route('client.deposit.create') }}" class="{{ $link(request()->routeIs('client.deposit.*')) }}"><i class="fa-solid fa-arrow-down w-5 text-center"></i> Deposit</a>
            <a href="{{ route('withdraw.create') }}" class="{{ $link(request()->routeIs('withdraw.*')) }}"><i class="fa-solid fa-money-bill-transfer w-5 text-center"></i> Withdraw</a>
            <a href="{{ route('accounts.index') }}" class="{{ $link(request()->routeIs('accounts.*')) }}"><i class="fa-solid fa-layer-group w-5 text-center"></i> My Account</a>
            <div class="pt-2 mt-2 border-t border-white/10"></div>
            <a href="{{ route('support.index') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('support.*') ? 'bg-emerald-500 text-[#04231a] font-semibold shadow' : 'hover:bg-white/10' }}"><i class="fa-solid fa-headset text-lg w-6 text-center"></i> <span class="font-medium">Support</span></a>
        </nav>
    </aside>

    {{-- Main --}}
    <div class="flex-1 lg:pl-64 pb-24 lg:pb-0">
        {{-- Top bar: logo+name left, notifications + profile right --}}
        <header class="bg-white border-b sticky top-0 z-30 safe-t">
            <div class="px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <img src="/logo.png" alt="" class="w-8 h-8 shrink-0" onerror="this.style.display='none'">
                    <span class="font-bold text-[#0a1730] truncate">Growth<span class="text-emerald-500">Capital</span></span>
                </div>
                <x-notification-bell />
            </div>
        </header>

        <main class="px-4 sm:px-6 lg:px-8 py-6">
            @if (session('status'))
                <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3">{{ session('status') }}</div>
            @endif
            {{ $slot }}
        </main>
    </div>

    {{-- Deposit/Withdraw action sheet (mobile +) --}}
    <div x-show="sheet" x-transition.opacity @click="sheet=false" style="display:none" class="lg:hidden fixed inset-0 bg-black/40 z-40"></div>
    <div x-show="sheet" x-transition x-cloak class="lg:hidden fixed inset-x-0 bottom-20 z-50 px-6 safe-b">
        <div class="bg-white rounded-2xl shadow-xl p-3 max-w-sm mx-auto grid grid-cols-2 gap-3">
            <a href="{{ route('client.deposit.create') }}" class="flex flex-col items-center gap-1 py-4 rounded-xl bg-emerald-50 text-emerald-700">
                <i class="fa-solid fa-arrow-down text-xl"></i><span class="text-sm font-medium">Deposit</span>
            </a>
            <a href="{{ route('withdraw.create') }}" class="flex flex-col items-center gap-1 py-4 rounded-xl bg-amber-50 text-amber-700">
                <i class="fa-solid fa-money-bill-transfer text-xl"></i><span class="text-sm font-medium">Withdraw</span>
            </a>
        </div>
    </div>

    {{-- Mobile bottom tab bar --}}
    <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t z-40 safe-b">
        <div class="grid grid-cols-5 items-end px-1">
            @php $tab = 'tab flex flex-col items-center gap-0.5 text-[11px] py-2 text-gray-400'; @endphp
            <a href="{{ route('client.dashboard') }}" class="{{ $tab }} {{ request()->routeIs('client.dashboard') ? 'is-active' : '' }}">
                <i class="fa-solid fa-house tab-ico text-lg"></i>Home</a>
            <a href="{{ route('client.profit') }}" class="{{ $tab }} {{ request()->routeIs('client.profit') ? 'is-active' : '' }}">
                <i class="fa-solid fa-chart-line tab-ico text-lg"></i>History</a>
            <button type="button" @click="sheet=!sheet" class="flex flex-col items-center -mt-5">
                <span class="w-14 h-14 rounded-full bg-emerald-600 text-white grid place-items-center shadow-lg shadow-emerald-600/30 ring-4 ring-gray-50 transition active:scale-95">
                    <i class="fa-solid fa-plus text-xl" :class="sheet ? 'rotate-45' : ''" style="transition:transform .2s"></i>
                </span>
            </button>
            <a href="{{ route('client.transactions') }}" class="{{ $tab }} {{ request()->routeIs('client.transactions') ? 'is-active' : '' }}">
                <i class="fa-solid fa-receipt tab-ico text-lg"></i>Transactions</a>
            <a href="{{ route('profile.edit') }}" class="{{ $tab }} {{ request()->routeIs('profile.edit') ? 'is-active' : '' }}">
                <i class="fa-solid fa-user tab-ico text-lg"></i>Profile</a>
        </div>
    </nav>
</div>

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); });
    }
</script>
</body>
</html>
