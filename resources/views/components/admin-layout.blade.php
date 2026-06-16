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
        <div class="px-6 py-5 border-b border-white/10 flex items-center gap-2.5">
            <img src="/logo.png" alt="" class="w-9 h-9 shrink-0" onerror="this.style.display='none'">
            <div class="text-white font-bold text-lg leading-tight">Growth<span class="text-emerald-400">Capital</span><span class="block text-xs font-normal text-gray-400">Fund Admin</span></div>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
            @php $nav = [
                ['admin.dashboard','Dashboard'],
                ['admin.clients.index','Clients'],
                ['admin.account-requests.index','Account Requests'],
                ['admin.kyc.index','KYC Review'],
                ['admin.account-types.index','Account Types'],
                ['admin.deposits.index','Deposits'],
                ['admin.withdrawals.index','Withdrawals'],
                ['admin.payment-methods.index','Payment Methods'],
                ['admin.transactions.index','Transactions'],
                ['admin.messages.index','Message Center'],
                ['admin.pool.index','Pool / PnL'],
                ['admin.settings.edit','Settings'],
            ]; @endphp
            @foreach ($nav as [$route, $label])
                <a href="{{ route($route) }}"
                   class="block px-3 py-2 rounded-md {{ request()->routeIs($route) || request()->routeIs(str_replace('.index','.*',$route)) ? 'bg-emerald-500 text-[#04231a] font-semibold' : 'hover:bg-white/10' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="p-3 border-t border-white/10">
            @csrf
            <button class="w-full text-left px-3 py-2 rounded-md hover:bg-white/10 text-sm">Log out</button>
        </form>
    </aside>

    {{-- Main --}}
    <div class="lg:pl-64">
        <header class="bg-white shadow-sm">
            <div class="px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-900">{{ $title }}</h1>
                <div class="flex items-center gap-3">
                    <button type="button" aria-label="Toggle theme"
                            onclick="var d=document.documentElement.classList.toggle('dark');localStorage.setItem('theme',d?'dark':'light');"
                            class="w-9 h-9 rounded-full grid place-items-center text-gray-500 hover:bg-gray-100">
                        <i class="fa-solid fa-moon dark:hidden"></i><i class="fa-solid fa-sun hidden dark:inline"></i>
                    </button>
                    <span class="text-sm text-gray-500">{{ auth()->user()->name }}</span>
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
