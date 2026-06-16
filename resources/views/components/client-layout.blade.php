@props(['title' => 'Dashboard'])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title }} · GrowthCapital Funds</title>
    <script>(function(){try{var t=localStorage.getItem('theme');if(t==='dark'||(!t&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark');}}catch(e){}})();</script>
    {{-- PWA --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0a1730">
    <link rel="apple-touch-icon" href="/icon.svg">
    <link rel="icon" href="/icon.svg" type="image/svg+xml">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="GrowthCapital">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 text-gray-800">
<div class="min-h-full lg:flex">

    {{-- Desktop sidebar --}}
    <aside class="hidden lg:flex lg:flex-col w-64 bg-[#0a1730] text-gray-300 fixed inset-y-0">
        <div class="px-6 py-5 text-white font-bold text-lg border-b border-white/10">Growth<span class="text-emerald-400">Capital</span><span class="block text-xs font-normal text-gray-400">Mutual Funds</span></div>
        <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
            @php $nav = [
                ['client.dashboard','Dashboard','M3 12l9-9 9 9M4 10v10h16V10'],
                ['profile.edit','Profile','M16 14a4 4 0 10-8 0'],
            ]; @endphp
            <a href="{{ route('client.dashboard') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('client.dashboard') ? 'bg-emerald-500 text-[#04231a] font-semibold' : 'hover:bg-white/10' }}"><i class="fa-solid fa-gauge w-5"></i> Dashboard</a>
            <a href="{{ route('client.profit') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('client.profit') ? 'bg-emerald-500 text-[#04231a] font-semibold' : 'hover:bg-white/10' }}"><i class="fa-solid fa-chart-line w-5"></i> Profit History</a>
            <a href="{{ route('client.transactions') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('client.transactions') ? 'bg-emerald-500 text-[#04231a] font-semibold' : 'hover:bg-white/10' }}"><i class="fa-solid fa-receipt w-5"></i> Transactions</a>
            <a href="{{ route('withdraw.create') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('withdraw.*') ? 'bg-emerald-500 text-[#04231a] font-semibold' : 'hover:bg-white/10' }}"><i class="fa-solid fa-money-bill-transfer w-5"></i> Withdraw</a>
            <a href="{{ route('accounts.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('accounts.*') ? 'bg-emerald-500 text-[#04231a] font-semibold' : 'hover:bg-white/10' }}"><i class="fa-solid fa-layer-group w-5"></i> My Accounts</a>
            <a href="{{ route('support.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('support.*') ? 'bg-emerald-500 text-[#04231a] font-semibold' : 'hover:bg-white/10' }}"><i class="fa-solid fa-headset w-5"></i> Support</a>
            <a href="{{ route('security.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('security.*') ? 'bg-emerald-500 text-[#04231a] font-semibold' : 'hover:bg-white/10' }}"><i class="fa-solid fa-shield-halved w-5"></i> Security</a>
            <a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('profile.edit') ? 'bg-emerald-500 text-[#04231a] font-semibold' : 'hover:bg-white/10' }}">Profile</a>
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="p-3 border-t border-white/10">@csrf
            <button class="w-full text-left px-3 py-2 rounded-md hover:bg-white/10 text-sm">Log out</button>
        </form>
    </aside>

    {{-- Main --}}
    <div class="flex-1 lg:pl-64 pb-20 lg:pb-0">
        {{-- Top bar --}}
        <header class="bg-white border-b sticky top-0 z-10">
            <div class="px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
                <div class="lg:hidden font-bold text-[#0a1730]">Growth<span class="text-emerald-500">Capital</span></div>
                <h1 class="hidden lg:block text-lg font-semibold text-gray-900">{{ $title }}</h1>
                <div class="flex items-center gap-2">
                    <button type="button" aria-label="Toggle theme"
                            onclick="var d=document.documentElement.classList.toggle('dark');localStorage.setItem('theme',d?'dark':'light');"
                            class="w-9 h-9 rounded-full grid place-items-center text-gray-500 hover:bg-gray-100">
                        <i class="fa-solid fa-moon dark:hidden"></i><i class="fa-solid fa-sun hidden dark:inline"></i>
                    </button>
                    <span class="text-sm text-gray-500 hidden sm:inline">{{ auth()->user()->name }}</span>
                    <div class="w-9 h-9 rounded-full bg-emerald-500 text-[#04231a] grid place-items-center font-bold text-sm">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
                </div>
            </div>
        </header>

        <main class="px-4 sm:px-6 lg:px-8 py-6">
            @if (session('status'))
                <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3">{{ session('status') }}</div>
            @endif
            {{ $slot }}
        </main>
    </div>

    {{-- Mobile app-style bottom tab bar --}}
    <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t flex justify-around py-2 z-20">
        @php $tab = 'flex flex-col items-center text-[11px] gap-0.5 px-3 py-1'; @endphp
        <a href="{{ route('client.dashboard') }}" class="{{ $tab }} {{ request()->routeIs('client.dashboard') ? 'text-emerald-600' : 'text-gray-400' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10h14V10"/></svg>Home</a>
        <a href="{{ route('client.transactions') }}" class="{{ $tab }} {{ request()->routeIs('client.transactions','client.profit') ? 'text-emerald-600' : 'text-gray-400' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"/></svg>History</a>
        <a href="{{ route('withdraw.create') }}" class="{{ $tab }} {{ request()->routeIs('withdraw.*') ? 'text-emerald-600' : 'text-gray-400' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v8m-4-4h8M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Withdraw</a>
        <a href="{{ route('support.index') }}" class="{{ $tab }} {{ request()->routeIs('support.*') ? 'text-emerald-600' : 'text-gray-400' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 13a3 3 0 01-3 3l-4 3v-3H8a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3z"/></svg>Support</a>
        <a href="{{ route('profile.edit') }}" class="{{ $tab }} {{ request()->routeIs('profile.edit') ? 'text-emerald-600' : 'text-gray-400' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 14a4 4 0 10-8 0M12 7a3 3 0 100 6 3 3 0 000-6z"/></svg>Profile</a>
    </nav>
</div>

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function () {});
        });
    }
</script>
</body>
</html>
