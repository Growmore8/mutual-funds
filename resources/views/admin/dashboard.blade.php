<x-admin-layout title="Dashboard">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <a href="{{ route('admin.clients.index') }}" class="bg-white shadow rounded-xl p-6 hover:shadow-md transition">
            <p class="text-sm text-gray-500">Clients</p>
            <p class="text-3xl font-bold text-gray-900">{{ $clients }}</p>
        </a>
        <a href="{{ route('admin.kyc.index') }}" class="bg-white shadow rounded-xl p-6 hover:shadow-md transition">
            <p class="text-sm text-gray-500">KYC pending review</p>
            <p class="text-3xl font-bold text-amber-600">{{ $pendingKyc }}</p>
        </a>
        <div class="bg-white shadow rounded-xl p-6">
            <p class="text-sm text-gray-500">Pool account</p>
            <p class="text-3xl font-bold text-gray-900">{{ $pool?->account_ref ?? '—' }}</p>
            <p class="text-xs text-gray-400">Capacity {{ number_format((float)($pool?->capacity ?? 0)) }} {{ $pool?->currency }}</p>
        </div>
    </div>
</x-admin-layout>
