{{-- Shared Holdings / Trades panels (expects Alpine `tab` in scope). Market-only — no resting orders. --}}
<div x-show="tab==='holdings'">
    @forelse ($holdings as $h)
        <div class="flex justify-between text-xs py-1.5"><span>{{ $h->instrument->symbol }} ×{{ rtrim(rtrim((string)$h->qty,'0'),'.') }}</span><span class="text-gray-400">avg {{ $h->instrument->currencySymbol() }}{{ number_format((float)$h->avg_price,2) }}</span></div>
    @empty <p class="text-xs text-gray-400 py-3 text-center">No holdings yet.</p> @endforelse
</div>
<div x-show="tab==='trades'" x-cloak>
    @forelse ($trades as $t)
        @php $isBuy = $t->buyer_id === auth()->id(); @endphp
        <div class="flex justify-between text-xs py-1.5"><span class="{{ $isBuy?'text-emerald-500':'text-red-500' }}">{{ $isBuy?'BUY':'SELL' }} {{ $t->instrument->symbol }} ×{{ rtrim(rtrim((string)$t->qty,'0'),'.') }}</span><span class="text-gray-400">{{ $t->instrument->currencySymbol() }}{{ number_format((float)$t->price,2) }} · {{ $t->created_at->format('d M H:i') }}</span></div>
    @empty <p class="text-xs text-gray-400 py-3 text-center">No trades yet.</p> @endforelse
</div>
