<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Admin Dashboard') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <p class="text-sm text-gray-500">Clients</p>
                <p class="text-3xl font-bold text-gray-900">{{ $clients }}</p>
            </div>
            <div class="bg-white shadow sm:rounded-lg p-6">
                <p class="text-sm text-gray-500">KYC pending review</p>
                <p class="text-3xl font-bold text-amber-600">{{ $pendingKyc }}</p>
            </div>
            <div class="bg-white shadow sm:rounded-lg p-6">
                <p class="text-sm text-gray-500">Pool account</p>
                <p class="text-3xl font-bold text-gray-900">{{ $pool?->account_ref ?? '—' }}</p>
                <p class="text-xs text-gray-400">Capacity {{ number_format((float)($pool?->capacity ?? 0)) }} {{ $pool?->currency }}</p>
            </div>
        </div>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
            <div class="bg-white shadow sm:rounded-lg p-6 text-sm text-gray-500">
                Full management (clients, transactions, account types, payment methods, KYC review, PnL distribution) is built in the next phases.
            </div>
        </div>
    </div>
</x-app-layout>
