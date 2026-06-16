<x-client-layout title="Profile">
    <div class="max-w-3xl space-y-6">
        {{-- Account header --}}
        <div class="p-6 bg-white rounded-2xl shadow-sm flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-emerald-500 text-[#04231a] grid place-items-center font-bold text-xl">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
            <div class="min-w-0">
                <p class="font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                <p class="text-sm text-gray-500 truncate">{{ auth()->user()->email }}</p>
            </div>
        </div>

        {{-- Quick settings --}}
        <div class="p-2 bg-white rounded-2xl shadow-sm divide-y divide-gray-100 text-sm">
            <a href="{{ route('security.index') }}" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 rounded-xl">
                <i class="fa-solid fa-shield-halved w-5 text-gray-400"></i> Security &amp; app lock <i class="fa-solid fa-chevron-right ml-auto text-gray-300"></i>
            </a>
            <button type="button" onclick="var d=document.documentElement.classList.toggle('dark');localStorage.setItem('theme',d?'dark':'light');"
                    class="w-full text-left flex items-center gap-3 px-4 py-3 hover:bg-gray-50 rounded-xl">
                <i class="fa-solid fa-moon w-5 text-gray-400 dark:hidden"></i><i class="fa-solid fa-sun w-5 text-gray-400 hidden dark:inline"></i>
                <span class="dark:hidden">Dark mode</span><span class="hidden dark:inline">Light mode</span>
            </button>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="w-full text-left flex items-center gap-3 px-4 py-3 hover:bg-gray-50 rounded-xl text-red-600"><i class="fa-solid fa-right-from-bracket w-5"></i> Log out</button>
            </form>
        </div>

        <div class="p-6 bg-white rounded-2xl shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="p-6 bg-white rounded-2xl shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="p-6 bg-white rounded-2xl shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-client-layout>
