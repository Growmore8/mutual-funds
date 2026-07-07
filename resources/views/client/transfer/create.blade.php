<x-client-layout title="Transfer & Reinvest" :embed="request()->boolean('embed')">
    <div class="max-w-md mx-auto" x-data="{
            dir: '{{ in_array(request('dir'), ['mf_reinvest','spot_reinvest','mf_to_spot','spot_to_mf']) ? request('dir') : 'mf_reinvest' }}',
            amount: '',
            actions: {
                mf_reinvest:   { from:'Mutual Fund profit', to:'Mutual Fund capital', avail: {{ (float) $mfWithdrawable }}, note:'Compound your fund — profit becomes invested capital.' },
                spot_reinvest: { from:'Spot profit',        to:'Spot capital',        avail: {{ (float) ($spotProfit ?? 0) }}, note:'Lock in spot gains as capital (money stays in your wallet).' },
                mf_to_spot:    { from:'Mutual Fund profit', to:'Spot wallet',         avail: {{ (float) $mfWithdrawable }}, note:'Move mutual-fund profit into your spot wallet to trade.' },
                spot_to_mf:    { from:'Spot wallet',        to:'Mutual Fund capital', avail: {{ (float) $spotUsd }},        note:'Move spot funds into your mutual-fund capital.' },
            },
            get cur(){ return this.actions[this.dir]; },
            get avail(){ return this.cur.avail; },
            max(){ this.amount = this.avail.toFixed(2); }
         }">
        <div class="flex items-center gap-3 mb-4">
            @unless (request()->boolean('embed'))<a href="{{ url()->previous() }}" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-arrow-left"></i></a>@endunless
            <h1 class="text-lg font-bold text-gray-900 dark:text-white">Transfer &amp; Reinvest</h1>
        </div>

        @if (session('status'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-300 text-sm rounded-lg p-3">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('transfer.store') }}" class="gcard rounded-2xl p-5 bg-white dark:bg-white/[0.04] space-y-4">
            @csrf
            <input type="hidden" name="direction" :value="dir">

            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">Action</label>
                <select x-model="dir" @change="amount=''" class="w-full bg-gray-50 dark:bg-white/5 border-gray-200 dark:border-white/10 rounded-lg text-sm py-3">
                    <option value="mf_reinvest">♻️ Reinvest Mutual Fund profit → capital</option>
                    <option value="spot_reinvest">♻️ Reinvest Spot profit → capital</option>
                    <option value="mf_to_spot">➡️ Move MF profit → Spot wallet</option>
                    <option value="spot_to_mf">⬅️ Move Spot → MF capital</option>
                </select>
            </div>

            <div class="flex items-center justify-between rounded-xl bg-gray-50 dark:bg-white/5 px-4 py-3 text-sm">
                <div><span class="text-[11px] text-gray-400 block">From</span><span class="font-semibold text-gray-900 dark:text-white" x-text="cur.from"></span></div>
                <i class="fa-solid fa-arrow-right text-emerald-500"></i>
                <div class="text-right"><span class="text-[11px] text-gray-400 block">To</span><span class="font-semibold text-gray-900 dark:text-white" x-text="cur.to"></span></div>
            </div>

            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">Amount (USD)</label>
                <div class="flex items-center gap-2 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg px-3">
                    <input type="number" step="0.01" min="0.01" name="amount" x-model="amount" placeholder="0.00" required class="flex-1 bg-transparent py-3 text-sm focus:outline-none border-0">
                    <button type="button" @click="max()" class="text-emerald-600 dark:text-emerald-400 font-semibold text-sm">Max</button>
                    <span class="text-gray-400 text-sm">USD</span>
                </div>
                <p class="text-xs text-gray-400 mt-1">Available: <span class="font-semibold text-gray-700 dark:text-gray-200" x-text="'$'+avail.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})"></span></p>
                <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-1"><i class="fa-solid fa-circle-info"></i> <span x-text="cur.note"></span></p>
            </div>

            <button type="submit" :disabled="!amount || parseFloat(amount)<=0 || parseFloat(amount)>avail+0.001"
                    class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold disabled:opacity-50">Confirm</button>
        </form>
    </div>
</x-client-layout>
