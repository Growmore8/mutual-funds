@props(['title' => 'Admin'])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · GrowthCapital Funds</title>
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/logo.png">
    <script>(function(){try{var t=localStorage.getItem('theme');if(t==='dark'||(!t&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark');}}catch(e){}})();</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-100 text-gray-800">
<div x-data="{ open: false }" class="min-h-full">
    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 w-64 bg-[#0a1730] text-gray-300 hidden lg:flex flex-col">
        <div class="px-6 h-16 shrink-0 border-b border-white/[0.06] flex items-center gap-2.5">
            <img src="/logo.png" alt="" class="w-9 h-9 shrink-0" onerror="this.style.display='none'">
            <div class="text-white font-bold text-lg leading-tight">Growth<span class="text-emerald-400">Capital</span><span class="block text-xs font-normal text-gray-400">Fund Admin</span></div>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1 text-sm overflow-y-auto">
            @php
                $active = 'bg-white/[0.08] text-white font-semibold ring-1 ring-white/10';
                $idle = 'text-gray-400 hover:bg-white/[0.05] hover:text-white';
                $base = 'flex items-center gap-3 px-3 py-2.5 rounded-xl transition';
                $link = fn ($route, $label, $icon) =>
                    '<a href="' . route($route) . '" class="' . $base . ' '
                    . (request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route)) ? $active : $idle)
                    . '"><i class="fa-solid ' . $icon . ' w-5 text-center"></i><span>' . $label . '</span></a>';
                $heading = fn ($t) => '<p class="px-3 pt-5 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-gray-500">' . $t . '</p>';
            @endphp

            {!! $link('admin.dashboard', 'Dashboard', 'fa-gauge-high') !!}

            {!! $heading('Clients') !!}
            {!! $link('admin.clients.index', 'Clients', 'fa-users') !!}
            {!! $link('admin.kyc.index', 'KYC Review', 'fa-id-card') !!}
            {!! $link('admin.account-types.index', 'Account Types', 'fa-layer-group') !!}

            {!! $heading('Finance') !!}
            @php
                $reqCount = \App\Models\Deposit::where('status', 'pending')->count()
                    + \App\Models\Withdrawal::where('status', 'pending')->count()
                    + \App\Models\AccountRequest::where('status', 'pending')->count();
                $reqActive = request()->routeIs('admin.deposits.*', 'admin.withdrawals.*', 'admin.account-requests.*');
            @endphp
            <a href="{{ route('admin.deposits.index') }}" class="{{ $base }} {{ $reqActive ? $active : $idle }}">
                <i class="fa-solid fa-inbox w-5 text-center"></i><span>Requests</span>
                @if ($reqCount > 0)
                    <span class="ml-auto text-[11px] min-w-5 h-5 px-1.5 grid place-items-center rounded-full bg-red-500 text-white font-semibold">{{ $reqCount }}</span>
                @endif
            </a>
            {!! $link('admin.transactions.index', 'Transactions', 'fa-receipt') !!}
            {!! $link('admin.payment-methods.index', 'Payment Methods', 'fa-credit-card') !!}

            {!! $heading('Fund') !!}
            <a href="{{ route('admin.pool.index') }}" class="{{ $base }} {{ request()->routeIs('admin.pool.index') ? $active : $idle }}"><i class="fa-solid fa-layer-group w-5 text-center"></i><span>Pool</span></a>
            <a href="{{ route('admin.pool.pnl') }}" class="{{ $base }} {{ request()->routeIs('admin.pool.pnl') ? $active : $idle }}"><i class="fa-solid fa-chart-pie w-5 text-center"></i><span>PnL</span></a>
            <a href="{{ route('admin.messages.index') }}" class="{{ $base }} {{ request()->routeIs('admin.messages.*') ? $active : $idle }}"><i class="fa-solid fa-headset w-5 text-center"></i><span>Message Center</span></a>

            {!! $heading('Settings') !!}
            {!! $link('admin.settings.edit', 'Profile', 'fa-user') !!}
            {!! $link('admin.settings.security', 'Security', 'fa-shield-halved') !!}
            <button type="button" onclick="var d=document.documentElement.classList.toggle('dark');localStorage.setItem('theme',d?'dark':'light');"
                    class="w-full text-left {{ $base }} {{ $idle }}">
                <i class="fa-solid fa-circle-half-stroke w-5 text-center"></i><span>Appearance</span>
            </button>
        </nav>
        {{-- Admin profile card + logout --}}
        <div class="p-3 border-t border-white/[0.06]">
            <div class="flex items-center gap-3 rounded-xl bg-white/[0.04] ring-1 ring-white/10 p-2.5">
                <div class="w-9 h-9 rounded-full bg-emerald-500 text-[#04231a] grid place-items-center font-bold shrink-0">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-gray-400 truncate">Administrator</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button title="Log out" class="w-8 h-8 grid place-items-center rounded-lg text-gray-400 hover:text-red-300 hover:bg-white/10"><i class="fa-solid fa-right-from-bracket"></i></button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main --}}
    <div class="lg:pl-64">
        <header class="bg-white shadow-sm">
            <div class="px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-900">{{ $title }}</h1>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500 hidden sm:inline">{{ auth()->user()->name }}</span>
                    <x-notification-bell :sound="true" />
                </div>
            </div>
        </header>
        <main class="px-4 sm:px-6 lg:px-8 py-8">
            @if (session('status'))
                <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3">{{ session('status') }}</div>
            @endif
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
