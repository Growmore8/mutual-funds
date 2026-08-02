<x-admin-layout title="Market Data / API">
    <div class="max-w-2xl space-y-6">
        @if (session('status'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3 break-words">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
        @endif

        <div class="bg-white shadow rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1"><i class="fa-solid fa-plug text-emerald-600"></i> Market Data / API</h2>
            <p class="text-sm text-gray-500">Manage the market-data API keys (spot prices &amp; chart candles) and the <strong>Pool</strong> API (mutual-fund PnL sync). Values saved here override the server <code>.env</code> — no server login needed.</p>
        </div>

        <form method="POST" action="{{ route('admin.settings.marketdata.update') }}" class="space-y-6 text-sm">
            @csrf

            {{-- Spot prices --}}
            <div class="bg-white shadow rounded-xl p-6 space-y-4">
                <h3 class="font-semibold text-gray-900">Spot Prices &amp; Candles</h3>
                <div>
                    <label class="block text-gray-700 mb-1">Prices URL</label>
                    <input name="cubex_prices_url" value="{{ old('cubex_prices_url', $cubexPricesUrl) }}" class="w-full border-gray-300 rounded-md font-mono text-xs" placeholder="https://your-trading-domain.com/api/external/v1/prices">
                </div>
                <div>
                    <label class="block text-gray-700 mb-1">Candles URL</label>
                    <input name="cubex_candles_url" value="{{ old('cubex_candles_url', $cubexCandlesUrl) }}" class="w-full border-gray-300 rounded-md font-mono text-xs" placeholder="https://your-trading-domain.com/api/external/v1/candles">
                    <p class="text-[11px] text-gray-400 mt-1">Leave blank to auto-derive from the prices URL (/prices → /candles).</p>
                </div>
                <div>
                    <label class="block text-gray-700 mb-1">API key</label>
                    <input name="cubex_api_key" type="text" autocomplete="off" class="w-full border-gray-300 rounded-md font-mono text-xs" placeholder="{{ $cubexKey ? '•••••••• (saved — leave blank to keep)' : 'ck_live_…' }}">
                </div>
            </div>

            {{-- Pool --}}
            <div class="bg-white shadow rounded-xl p-6 space-y-4">
                <h3 class="font-semibold text-gray-900">Pool API (mutual-fund PnL sync)</h3>
                <div>
                    <label class="block text-gray-700 mb-1">Pool URL</label>
                    <input name="pool_url" value="{{ old('pool_url', $poolUrl) }}" class="w-full border-gray-300 rounded-md font-mono text-xs" placeholder="https://…/api/external/v1/account-pnl">
                    <p class="text-[11px] text-gray-400 mt-1">If blank, the app uses simulated PnL (test mode).</p>
                </div>
                <div>
                    <label class="block text-gray-700 mb-1">Pool API key</label>
                    <input name="pool_key" type="text" autocomplete="off" class="w-full border-gray-300 rounded-md font-mono text-xs" placeholder="{{ $poolKey ? '•••••••• (saved — leave blank to keep)' : 'ck_live_…' }}">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button class="px-4 py-2 bg-emerald-600 text-white rounded-md">Save settings</button>
                <button form="mdtest" class="px-4 py-2 bg-gray-800 text-white rounded-md"><i class="fa-solid fa-vial"></i> Test connection</button>
            </div>
        </form>

        <form id="mdtest" method="POST" action="{{ route('admin.settings.marketdata.test') }}" class="hidden">@csrf</form>

        <p class="text-[11px] text-gray-400">Keys are stored in the database. Save first, then Test.</p>
    </div>
</x-admin-layout>
