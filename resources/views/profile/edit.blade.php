<x-client-layout title="Profile">
    <div class="max-w-3xl space-y-6">
        {{-- Account info (read-only) --}}
        <div class="p-6 bg-white dark:bg-white/[0.04] dark:border dark:border-white/[0.06] rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-emerald-500 text-[#04231a] grid place-items-center font-bold text-xl">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
            <div class="min-w-0">
                <p class="font-semibold text-gray-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 font-mono">{{ auth()->user()->clientCode() }}</p>
            </div>
        </div>

        {{-- Account switcher (only if the client has more than one fund account) --}}
        @php $accs = auth()->user()->fundAccounts; $current = auth()->user()->currentAccount(); @endphp
        @if ($accs->count() > 1)
            <div class="p-2 bg-white dark:bg-white/[0.04] dark:border dark:border-white/[0.06] rounded-2xl shadow-sm">
                <p class="px-4 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">Switch account</p>
                @foreach ($accs as $acc)
                    <form method="POST" action="{{ route('accounts.switch', $acc) }}">@csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-left {{ $current && $current->id === $acc->id ? 'bg-emerald-50 dark:bg-emerald-500/10' : 'hover:bg-gray-50 dark:hover:bg-white/5' }}">
                            <span class="w-9 h-9 rounded-full grid place-items-center font-bold shrink-0 {{ $current && $current->id === $acc->id ? 'bg-emerald-500 text-[#04231a]' : 'bg-gray-200 text-gray-600 dark:bg-white/10 dark:text-gray-300' }}">{{ $loop->iteration }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-medium text-gray-900 dark:text-white">{{ $acc->label }}</span>
                                <span class="block text-xs text-gray-400">{{ $acc->accountType->name ?? 'No plan' }} · {{ $acc->code() }}</span>
                            </span>
                            @if ($current && $current->id === $acc->id)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300">Current</span>
                            @else
                                <i class="fa-solid fa-arrow-right-arrow-left text-gray-300 dark:text-gray-500"></i>
                            @endif
                        </button>
                    </form>
                @endforeach
            </div>
        @endif

        {{-- Settings --}}
        <div class="p-2 bg-white dark:bg-white/[0.04] dark:border dark:border-white/[0.06] rounded-2xl shadow-sm divide-y divide-gray-100 dark:divide-white/[0.06] text-sm">
            <a href="{{ route('accounts.index') }}" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-white/5 rounded-xl text-gray-800 dark:text-gray-200">
                <i class="fa-solid fa-circle-plus w-5 text-gray-400"></i> Open another account <i class="fa-solid fa-chevron-right ml-auto text-gray-300 dark:text-gray-600"></i>
            </a>
            <a href="{{ route('security.index') }}" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-white/5 rounded-xl text-gray-800 dark:text-gray-200">
                <i class="fa-solid fa-shield-halved w-5 text-gray-400"></i> Security (PIN, biometric) <i class="fa-solid fa-chevron-right ml-auto text-gray-300 dark:text-gray-600"></i>
            </a>
            <x-statement-modal :base-url="route('client.statement')" class="w-full text-left flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-white/5 rounded-xl text-gray-800 dark:text-gray-200">
                <i class="fa-solid fa-file-pdf w-5 text-gray-400"></i> Statement (PDF) <i class="fa-solid fa-chevron-right ml-auto text-gray-300 dark:text-gray-600"></i>
            </x-statement-modal>
            <a href="{{ route('payout.index') }}" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-white/5 rounded-xl text-gray-800 dark:text-gray-200">
                <i class="fa-solid fa-money-check-dollar w-5 text-gray-400"></i> Withdrawal methods <i class="fa-solid fa-chevron-right ml-auto text-gray-300 dark:text-gray-600"></i>
            </a>
            <a href="{{ route('client.referrals') }}" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-white/5 rounded-xl text-gray-800 dark:text-gray-200">
                <i class="fa-solid fa-gift w-5 text-gray-400"></i> Refer &amp; Earn <i class="fa-solid fa-chevron-right ml-auto text-gray-300 dark:text-gray-600"></i>
            </a>
            <button type="button" onclick="var d=document.documentElement.classList.toggle('dark');localStorage.setItem('theme',d?'dark':'light');"
                    class="w-full text-left flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-white/5 rounded-xl text-gray-800 dark:text-gray-200">
                <i class="fa-solid fa-moon w-5 text-gray-400 dark:hidden"></i><i class="fa-solid fa-sun w-5 text-gray-400 hidden dark:inline"></i>
                <span class="dark:hidden">Dark mode</span><span class="hidden dark:inline">Light mode</span>
            </button>
        </div>

        {{-- Logout (last) --}}
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-white dark:bg-white/[0.04] dark:border dark:border-white/[0.06] rounded-2xl shadow-sm text-red-600 dark:text-red-400 font-medium hover:bg-red-50 dark:hover:bg-red-500/10">
                <i class="fa-solid fa-right-from-bracket"></i> Log out
            </button>
        </form>

        {{-- Need Help + footer --}}
        <div class="rounded-2xl border border-gray-200 dark:border-white/[0.06] bg-white dark:bg-white/[0.04] p-4">
            <p class="font-semibold text-gray-900 dark:text-white text-sm">Need Help?</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Our support team is here to help you.</p>
            <a href="{{ route('support.index') }}" class="mt-3 flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold"><i class="fa-solid fa-headset"></i> Contact Support</a>
        </div>
        <div class="text-xs text-gray-400 dark:text-gray-500 px-1">
            <p class="font-semibold text-gray-600 dark:text-gray-300">GrowthCapital Ltd.</p>
            <p>© {{ date('Y') }} All rights reserved.</p>
        </div>
    </div>
</x-client-layout>
