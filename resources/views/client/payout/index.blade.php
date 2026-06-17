<x-client-layout title="Payout Methods">
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Payout methods</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Save where you want withdrawals sent. You'll pick one when requesting a withdrawal.</p>
        </div>

        @if (session('status'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
        @endif

        {{-- Saved methods --}}
        <div class="space-y-2.5">
            @forelse ($methods as $m)
                <div class="rounded-2xl bg-white dark:bg-white/[0.04] border border-gray-100 dark:border-white/[0.06] px-4 py-3 flex items-center gap-3">
                    <span class="w-11 h-11 rounded-xl grid place-items-center text-white shrink-0 {{ $m->type==='crypto' ? 'bg-amber-500' : ($m->type==='upi' ? 'bg-purple-500' : 'bg-blue-600') }}">
                        <i class="fa-solid {{ $m->type==='crypto' ? 'fa-coins' : ($m->type==='upi' ? 'fa-mobile-screen' : 'fa-building-columns') }}"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $m->title() }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $m->summary() }}</p>
                    </div>
                    <form method="POST" action="{{ route('payout.destroy', $m) }}" onsubmit="return confirm('Remove this payout method?')">@csrf @method('DELETE')
                        <button class="w-8 h-8 grid place-items-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10" title="Remove"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            @empty
                <div class="rounded-2xl bg-white dark:bg-white/[0.04] border border-gray-100 dark:border-white/[0.06] px-4 py-8 text-center text-gray-400">No payout methods yet. Add one below.</div>
            @endforelse
        </div>

        {{-- Add new --}}
        <div class="rounded-2xl bg-white dark:bg-white/[0.04] border border-gray-100 dark:border-white/[0.06] p-5">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Add a payout method</h3>
            <form method="POST" action="{{ route('payout.store') }}" class="space-y-3 text-sm" x-data="{ type: 'crypto' }">
                @csrf
                @php $inp = 'w-full border-gray-300 rounded-lg dark:bg-white/10 dark:border-white/10 dark:text-white'; @endphp
                <div>
                    <label class="block text-gray-600 dark:text-gray-300 mb-1">Type</label>
                    <select name="type" x-model="type" class="{{ $inp }}">
                        <option value="crypto">Crypto wallet</option>
                        <option value="upi">UPI</option>
                        <option value="bank">Bank account</option>
                    </select>
                </div>

                <div x-show="type==='crypto'" class="grid grid-cols-2 gap-3">
                    <div><label class="block text-gray-600 dark:text-gray-300 mb-1">Network</label>
                        <select name="network" class="{{ $inp }}"><option>BEP20</option><option>ERC20</option><option>TRC20</option></select></div>
                    <div><label class="block text-gray-600 dark:text-gray-300 mb-1">Currency</label><input name="currency" value="USDT" class="{{ $inp }}"></div>
                    <div class="col-span-2"><label class="block text-gray-600 dark:text-gray-300 mb-1">Wallet address</label><input name="wallet" class="{{ $inp }}" placeholder="0x… / T…"></div>
                </div>

                <div x-show="type==='upi'" x-cloak class="grid grid-cols-2 gap-3">
                    <div><label class="block text-gray-600 dark:text-gray-300 mb-1">Provider</label><input name="provider" class="{{ $inp }}" placeholder="GPay / PhonePe"></div>
                    <div><label class="block text-gray-600 dark:text-gray-300 mb-1">UPI ID</label><input name="upi_id" class="{{ $inp }}" placeholder="name@bank"></div>
                </div>

                <div x-show="type==='bank'" x-cloak class="grid grid-cols-2 gap-3">
                    <div><label class="block text-gray-600 dark:text-gray-300 mb-1">Account name</label><input name="account_name" class="{{ $inp }}"></div>
                    <div><label class="block text-gray-600 dark:text-gray-300 mb-1">Account number</label><input name="account_number" class="{{ $inp }}"></div>
                    <div><label class="block text-gray-600 dark:text-gray-300 mb-1">Bank name</label><input name="bank_name" class="{{ $inp }}"></div>
                    <div><label class="block text-gray-600 dark:text-gray-300 mb-1">IFSC code</label><input name="ifsc" class="{{ $inp }}"></div>
                </div>

                <button class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold"><i class="fa-solid fa-plus mr-1"></i> Add method</button>
            </form>
        </div>
    </div>
</x-client-layout>
