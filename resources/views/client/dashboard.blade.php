<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Dashboard') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Welcome, {{ $user->name }}</h3>
                <p class="text-sm text-gray-500 mt-1">Your account is verified. The full investor dashboard (pool overview, earnings, transactions) is coming in the next phase.</p>
            </div>
        </div>
    </div>
</x-app-layout>
