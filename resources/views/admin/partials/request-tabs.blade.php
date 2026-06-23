@php
    $tabPend = [
        'deposits'    => \App\Models\Deposit::where('status', 'pending')->count(),
        'withdrawals' => \App\Models\Withdrawal::where('status', 'pending')->count(),
        'accounts'    => \App\Models\AccountRequest::where('status', 'pending')->count(),
    ];
    $reqTabs = [
        ['route' => 'admin.deposits.index',         'match' => 'admin.deposits.*',         'label' => 'Deposits',         'key' => 'deposits',    'icon' => 'fa-arrow-down-to-bracket'],
        ['route' => 'admin.withdrawals.index',      'match' => 'admin.withdrawals.*',      'label' => 'Withdrawals',      'key' => 'withdrawals', 'icon' => 'fa-money-bill-transfer'],
        ['route' => 'admin.account-requests.index', 'match' => 'admin.account-requests.*', 'label' => 'Account Requests', 'key' => 'accounts',    'icon' => 'fa-folder-plus'],
    ];
@endphp
<div class="flex items-center gap-2 mb-5 overflow-x-auto">
    @foreach ($reqTabs as $t)
        @php $active = request()->routeIs($t['match']); @endphp
        <a href="{{ route($t['route']) }}"
           class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg font-semibold whitespace-nowrap {{ $active ? 'bg-emerald-600 text-white shadow' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
            <i class="fa-solid {{ $t['icon'] }}"></i> {{ $t['label'] }}
            @if ($tabPend[$t['key']] > 0)
                <span class="text-[11px] min-w-5 h-5 px-1.5 grid place-items-center rounded-full {{ $active ? 'bg-white/25 text-white' : 'bg-red-500 text-white' }}">{{ $tabPend[$t['key']] }}</span>
            @endif
        </a>
    @endforeach
</div>
