<x-client-layout title="Profile">
    <div class="max-w-3xl space-y-6">
        {{-- Account info (read-only) --}}
        <div class="p-6 bg-white rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-emerald-500 text-[#04231a] grid place-items-center font-bold text-xl">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
            <div class="min-w-0">
                <p class="font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                <p class="text-sm text-gray-500 truncate">{{ auth()->user()->email }}</p>
                <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ auth()->user()->clientCode() }}</p>
            </div>
        </div>

        {{-- Settings --}}
        <div class="p-2 bg-white rounded-2xl shadow-sm divide-y divide-gray-100 text-sm">
            <a href="{{ route('security.index') }}" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 rounded-xl">
                <i class="fa-solid fa-shield-halved w-5 text-gray-400"></i> Security (PIN, biometric) <i class="fa-solid fa-chevron-right ml-auto text-gray-300"></i>
            </a>
            <button type="button" onclick="var d=document.documentElement.classList.toggle('dark');localStorage.setItem('theme',d?'dark':'light');"
                    class="w-full text-left flex items-center gap-3 px-4 py-3 hover:bg-gray-50 rounded-xl">
                <i class="fa-solid fa-moon w-5 text-gray-400 dark:hidden"></i><i class="fa-solid fa-sun w-5 text-gray-400 hidden dark:inline"></i>
                <span class="dark:hidden">Dark mode</span><span class="hidden dark:inline">Light mode</span>
            </button>
        </div>

        {{-- Logout (last) --}}
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-white rounded-2xl shadow-sm text-red-600 font-medium hover:bg-red-50">
                <i class="fa-solid fa-right-from-bracket"></i> Log out
            </button>
        </form>
    </div>
</x-client-layout>
