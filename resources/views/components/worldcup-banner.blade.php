@php
    $cfg = config('promo.worldcup');
    $active = ($cfg['enabled'] ?? false) && now()->lt(\Illuminate\Support\Carbon::parse($cfg['until'] ?? '2026-07-20')->endOfDay());
@endphp
@if ($active)
    <div x-data="{ show: (localStorage.getItem('wc26_hide') !== '1') }" x-show="show" x-cloak
         class="mb-3 mx-1 rounded-2xl overflow-hidden relative"
         style="background:linear-gradient(110deg,#0a1730 0%,#0b3b32 55%,#16c784 130%)">
        <div class="px-4 py-3 flex items-center gap-3">
            <span class="text-2xl">⚽</span>
            <div class="flex-1 min-w-0">
                <p class="text-white font-extrabold text-sm leading-tight">World Cup 2026 is here</p>
                <p class="text-[11px] text-emerald-100/90">Trade the brands behind the tournament — Coca-Cola, Visa, Nike, McDonald's & more.</p>
            </div>
            <a href="{{ route('spot.index', ['wc' => 1]) }}" class="shrink-0 px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow">View watchlist</a>
            <button @click="show=false; localStorage.setItem('wc26_hide','1')" class="shrink-0 text-white/70 hover:text-white text-sm px-1" aria-label="Dismiss">✕</button>
        </div>
    </div>
@endif
