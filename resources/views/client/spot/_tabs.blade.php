{{-- Shared Orders / Holdings / Trades panels (expects Alpine `tab` in scope) --}}
<div x-show="tab==='orders'">
    @forelse ($orders as $o)
        <div class="flex justify-between items-center text-xs py-1.5">
            <span class="{{ $o->side==='buy'?'text-emerald-500':'text-red-500' }} font-semibold">{{ strtoupper($o->side) }}</span>
            <span class="flex-1 px-2 text-gray-500">{{ $o->instrument->symbol }} {{ rtrim(rtrim((string)$o->remaining(),'0'),'.') }} @ {{ $o->price ? $o->instrument->currencySymbol().number_format((float)$o->price,2) : 'mkt' }}</span>
            <form method="POST" action="{{ route('spot.cancel',$o) }}">@csrf<button class="text-gray-400 hover:text-red-500">cancel</button></form>
        </div>
    @empty <p class="text-xs text-gray-400 py-3 text-center">No open orders.</p> @endforelse
</div>
<div x-show="tab==='holdings'" x-cloak>
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
