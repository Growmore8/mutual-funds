<x-client-layout title="Transfer">
    <div class="max-w-md mx-auto" x-data="{
            dir: 'spot_to_mf',
            spot: {{ (float) $spotUsd }},
            mf: {{ (float) $mfWithdrawable }},
            amount: '',
            get fromLabel(){ return this.dir==='spot_to_mf' ? 'Spot wallet' : 'Mutual Fund (profit)'; },
            get toLabel(){ return this.dir==='spot_to_mf' ? 'Mutual Fund' : 'Spot wallet'; },
            get avail(){ return this.dir==='spot_to_mf' ? this.spot : this.mf; },
            flip(){ this.dir = this.dir==='spot_to_mf' ? 'mf_to_spot' : 'spot_to_mf'; this.amount=''; },
            max(){ this.amount = this.avail.toFixed(2); }
         }">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ url()->previous() }}" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white">Within Account Transfer</h1>
        </div>

        @if (session('status'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-300 text-sm rounded-lg p-3">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('transfer.store') }}" class="gcard rounded-2xl p-5 bg-white dark:bg-white/[0.04] space-y-4">
            @csrf
            <input type="hidden" name="direction" :value="dir">

            <div class="relative">
                <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-white/10">
                    <span class="text-xs text-gray-400 w-12">From</span>
                    <span class="font-semibold text-gray-900 dark:text-white" x-text="fromLabel"></span>
                </div>
                <button type="button" @click="flip()" class="absolute left-1/2 -translate-x-1/2 -translate-y-1/2 top-1/2 w-9 h-9 grid place-items-center rounded-full bg-emerald-600 text-white shadow"><i class="fa-solid fa-arrow-down-up-across-line text-xs"></i></button>
                <div class="flex items-center justify-between py-3">
                    <span class="text-xs text-gray-400 w-12">To</span>
                    <span class="font-semibold text-gray-900 dark:text-white" x-text="toLabel"></span>
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">Amount (USD)</label>
                <div class="flex items-center gap-2 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg px-3">
                    <input type="number" step="0.01" min="0.01" name="amount" x-model="amount" placeholder="0.00" required class="flex-1 bg-transparent py-3 text-sm focus:outline-none border-0">
                    <button type="button" @click="max()" class="text-emerald-600 dark:text-emerald-400 font-semibold text-sm">Max</button>
                    <span class="text-gray-400 text-sm">USD</span>
                </div>
                <p class="text-xs text-gray-400 mt-1">Available: <span class="font-semibold text-gray-700 dark:text-gray-200" x-text="'$'+avail.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})"></span></p>
                <p x-show="dir==='mf_to_spot'" x-cloak class="text-[11px] text-amber-600 mt-1"><i class="fa-solid fa-circle-info"></i> Only mutual-fund profit can be moved (not your invested capital).</p>
            </div>

            <button type="submit" :disabled="!amount || parseFloat(amount)<=0 || parseFloat(amount)>avail+0.001"
                    class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold disabled:opacity-50">Confirm</button>
        </form>
    </div>
</x-client-layout>
