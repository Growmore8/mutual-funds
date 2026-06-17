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
<div class="flex items-end gap-1 mb-5 border-b border-gray-200 overflow-x-auto">
    @foreach ($reqTabs as $t)
        @php $active = request()->routeIs($t['match']); @endphp
        <a href="{{ route($t['route']) }}"
           class="flex items-center gap-2 px-4 py-2.5 text-sm rounded-t-lg whitespace-nowrap -mb-px border-b-2 {{ $active ? 'border-emerald-600 text-emerald-700 font-semibold bg-white' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
            <i class="fa-solid {{ $t['icon'] }}"></i> {{ $t['label'] }}
            @if ($tabPend[$t['key']] > 0)
                <span class="text-[11px] min-w-5 h-5 px-1.5 grid place-items-center rounded-full {{ $active ? 'bg-emerald-600 text-white' : 'bg-red-500 text-white' }}">{{ $tabPend[$t['key']] }}</span>
            @endif
        </a>
    @endforeach
</div>
