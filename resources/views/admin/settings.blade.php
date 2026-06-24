<x-admin-layout title="Profile">
    <div class="max-w-2xl space-y-6">
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
        @endif

        @if (session('status'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3">{{ session('status') }}</div>
        @endif

        {{-- Exchange rate (USD/INR markup) --}}
        <div class="bg-white shadow rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Exchange rate (USD / INR)</h2>
            <p class="text-sm text-gray-500 mb-4">Live mid-market rate plus your markup is used everywhere (deposits, withdrawals, conversions).</p>
            <div class="grid grid-cols-3 gap-3 mb-4 text-center">
                <div class="rounded-lg border p-3"><p class="text-xs text-gray-400">Live rate</p><p class="text-lg font-bold text-gray-900">₹{{ number_format($fxLive ?? 0, 2) }}</p></div>
                <div class="rounded-lg border p-3"><p class="text-xs text-gray-400">Your markup</p><p class="text-lg font-bold text-gray-900">+₹{{ number_format($fxMarkup ?? 0, 2) }}</p></div>
                <div class="rounded-lg border p-3 bg-emerald-50"><p class="text-xs text-gray-400">Effective</p><p class="text-lg font-bold text-emerald-700">₹{{ number_format($fxEffective ?? 0, 2) }}</p></div>
            </div>
            <form method="POST" action="{{ route('admin.settings.fx') }}" class="flex flex-wrap items-end gap-3 text-sm">
                @csrf
                <div>
                    <label class="block text-gray-700 mb-1">Markup added per $1 (INR)</label>
                    <input type="number" step="0.01" name="fx_inr_markup" value="{{ $fxMarkup ?? 0 }}" class="border-gray-300 rounded-md w-40" placeholder="e.g. 2">
                </div>
                <button class="px-4 py-2 bg-emerald-600 text-white rounded-md">Save rate</button>
            </form>
        </div>

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
    </div>
</x-admin-layout>
