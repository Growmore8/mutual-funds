<x-admin-layout title="Exchange Rate">
    <div class="max-w-2xl space-y-6">
        @if (session('status'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
        @endif

        <div class="bg-white shadow rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Exchange rate markup</h2>
            <p class="text-sm text-gray-500 mb-4">The base currency is <strong>USD</strong>. This markup % is added on top of the <strong>live rate of every currency</strong> — so it applies to Indian clients (INR) and clients registered in any other country (AED, GBP, …) for all deposits, withdrawals and conversions.</p>

            <form method="POST" action="{{ route('admin.settings.fx') }}" class="flex flex-wrap items-end gap-3 text-sm mb-5">
                @csrf
                <div>
                    <label class="block text-gray-700 mb-1">Markup %</label>
                    <input type="number" step="0.01" min="0" max="50" name="fx_markup_pct" value="{{ $pct }}" class="border-gray-300 rounded-md w-40" placeholder="e.g. 2">
                </div>
                <button class="px-4 py-2 bg-emerald-600 text-white rounded-md">Save markup</button>
            </form>

            @php $factor = 1 + $pct / 100; @endphp
            <div x-data="{ q:'', rows:@js($samples->map(fn($eff,$code)=>['code'=>$code,'eff'=>$eff,'live'=>round($eff/$factor,4)])->values()) }">
                <div class="relative mb-2">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input x-model="q" type="text" placeholder="Search currency (e.g. INR, AED, GBP…)" autocomplete="off"
                           class="w-full pl-8 border-gray-300 rounded-md text-sm">
                </div>
                <div class="rounded-lg border border-gray-200 overflow-hidden">
                    <div class="px-4 py-2 bg-gray-50 text-xs text-gray-500 flex justify-between"><span>Currency (per $1)</span><span>Live → Effective ({{ rtrim(rtrim(number_format($pct, 2), '0'), '.') }}%)</span></div>
                    <div class="max-h-96 overflow-y-auto">
                        <template x-for="r in rows.filter(x => x.code.toLowerCase().includes(q.toLowerCase()))" :key="r.code">
                            <div class="px-4 py-2.5 flex items-center justify-between text-sm border-t border-gray-100">
                                <span class="font-semibold text-gray-800 dark:text-gray-100" x-text="r.code"></span>
                                <span class="text-gray-700 dark:text-gray-300"><span class="text-gray-400" x-text="r.live.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})"></span> → <span x-text="r.eff.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})"></span></span>
                            </div>
                        </template>
                        <div x-show="rows.filter(x => x.code.toLowerCase().includes(q.toLowerCase())).length === 0" class="px-4 py-6 text-center text-gray-400 text-sm">No currency matches.</div>
                    </div>
                </div>
                <p class="text-[11px] text-gray-400 mt-3"><span x-text="rows.length"></span> currencies · live mid-market × {{ rtrim(rtrim(number_format($pct, 2), '0'), '.') }}% markup. INR live ₹{{ number_format($liveInr, 2) }} → ₹{{ number_format($effInr, 2) }} effective.</p>
            </div>
        </div>
    </div>
</x-admin-layout>
