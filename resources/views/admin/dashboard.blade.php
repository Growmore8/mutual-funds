<x-admin-layout title="Dashboard">
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
        <a href="{{ route('admin.clients.index') }}" class="bg-white shadow rounded-xl p-6 hover:shadow-md transition">
            <p class="text-sm text-gray-500"><i class="fa-solid fa-users text-gray-400 mr-1"></i> Clients</p>
            <p class="text-3xl font-bold text-gray-900">{{ $clients }}</p>
        </a>
        <a href="{{ route('admin.kyc.index') }}" class="bg-white shadow rounded-xl p-6 hover:shadow-md transition">
            <p class="text-sm text-gray-500"><i class="fa-solid fa-id-card text-gray-400 mr-1"></i> KYC pending review</p>
            <p class="text-3xl font-bold text-amber-600">{{ $pendingKyc }}</p>
        </a>
        <a href="{{ route('admin.withdrawals.index', ['status' => 'pending']) }}" class="bg-white shadow rounded-xl p-6 hover:shadow-md transition">
            <p class="text-sm text-gray-500"><i class="fa-solid fa-money-bill-transfer text-gray-400 mr-1"></i> Withdrawals pending</p>
            <p class="text-3xl font-bold text-amber-600">{{ $pendingWithdrawals }}</p>
        </a>
        <a href="{{ route('admin.messages.index', ['status' => 'open']) }}" class="bg-white shadow rounded-xl p-6 hover:shadow-md transition">
            <p class="text-sm text-gray-500"><i class="fa-solid fa-headset text-gray-400 mr-1"></i> Open tickets</p>
            <p class="text-3xl font-bold text-emerald-600">{{ $openTickets }}</p>
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
        <a href="{{ route('admin.account-requests.index', ['status' => 'pending']) }}" class="bg-white shadow rounded-xl p-6 hover:shadow-md transition">
            <p class="text-sm text-gray-500"><i class="fa-solid fa-folder-plus text-gray-400 mr-1"></i> Account requests pending</p>
            <p class="text-3xl font-bold text-amber-600">{{ $pendingAccountRequests }}</p>
        </a>
        <div class="bg-white shadow rounded-xl p-6">
            <p class="text-sm text-gray-500"><i class="fa-solid fa-layer-group text-gray-400 mr-1"></i> Pool account</p>
            <p class="text-3xl font-bold text-gray-900">{{ $pool?->account_ref ?? '—' }}</p>
            <p class="text-xs text-gray-400">Capacity {{ number_format((float)($pool?->capacity ?? 0)) }} {{ $pool?->currency }}</p>
        </div>
    </div>
</x-admin-layout>
