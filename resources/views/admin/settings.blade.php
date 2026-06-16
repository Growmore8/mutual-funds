<x-admin-layout title="Settings">
    <div class="max-w-2xl space-y-6">
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
        @endif

        {{-- Profile / email --}}
        <div class="bg-white shadow rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Admin profile</h2>
            <p class="text-sm text-gray-500 mb-5">Your name and login email.</p>
            <form method="POST" action="{{ route('admin.settings.profile') }}" class="space-y-4 text-sm">
                @csrf @method('PATCH')
                <div><label class="block text-gray-700 mb-1">Name</label><input name="name" value="{{ old('name',$admin->name) }}" required class="w-full border-gray-300 rounded-md"></div>
                <div><label class="block text-gray-700 mb-1">Email (login)</label><input type="email" name="email" value="{{ old('email',$admin->email) }}" required class="w-full border-gray-300 rounded-md"></div>
                <button class="px-4 py-2 bg-emerald-600 text-white rounded-md">Save profile</button>
            </form>
        </div>

        {{-- Password --}}
        <div class="bg-white shadow rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Change password</h2>
            <p class="text-sm text-gray-500 mb-5">Enter your current password to set a new one.</p>
            <form method="POST" action="{{ route('admin.settings.password') }}" class="space-y-4 text-sm">
                @csrf @method('PUT')
                <div><label class="block text-gray-700 mb-1">Current password</label><input type="password" name="current_password" required class="w-full border-gray-300 rounded-md"></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><label class="block text-gray-700 mb-1">New password</label><input type="password" name="password" required class="w-full border-gray-300 rounded-md"></div>
                    <div><label class="block text-gray-700 mb-1">Confirm new password</label><input type="password" name="password_confirmation" required class="w-full border-gray-300 rounded-md"></div>
                </div>
                <button class="px-4 py-2 bg-gray-800 text-white rounded-md">Change password</button>
            </form>
        </div>

        {{-- Appearance --}}
        <div class="bg-white shadow rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Appearance</h2>
            <p class="text-sm text-gray-500 mb-4">Switch between light and dark mode.</p>
            <button type="button"
                    onclick="var d=document.documentElement.classList.toggle('dark');localStorage.setItem('theme',d?'dark':'light');"
                    class="inline-flex items-center gap-2 px-4 py-2 border rounded-md text-sm">
                <i class="fa-solid fa-moon dark:hidden"></i><i class="fa-solid fa-sun hidden dark:inline"></i>
                <span class="dark:hidden">Switch to dark mode</span><span class="hidden dark:inline">Switch to light mode</span>
            </button>
        </div>

        {{-- Logout --}}
        <div class="bg-white shadow rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Session</h2>
            <p class="text-sm text-gray-500 mb-4">Sign out of the admin panel.</p>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-md text-sm"><i class="fa-solid fa-right-from-bracket"></i> Log out</button>
            </form>
        </div>
    </div>
</x-admin-layout>
