<x-client-layout title="Deposit">
    @php
        $money = fn ($n) => '$' . number_format((float) $n, 2);
        $methodsJson = $methods->map(fn ($m) => [
            'label' => trim($m->name . ($m->type === 'crypto' && $m->network ? ' · ' . $m->network : '')),
            'type' => $m->type,
            'network' => $m->type === 'crypto' ? $m->network : null,
            'currency' => $m->currency,
            'address' => $m->address,
            'instructions' => $m->instructions,
            'details' => $m->details ?? [],
        ])->values();
    @endphp

    <div class="max-w-5xl mx-auto"
         x-data="{ sel: null, copied: false, purpose: '{{ $purpose ?? 'fund' }}', currency: '{{ $currency ?? 'USD' }}', methods: {{ Illuminate\Support\Js::from($methodsJson) }},
                   copy(t){ try{ navigator.clipboard.writeText(t || ''); }catch(e){} this.copied = true; clearTimeout(this._ct); this._ct = setTimeout(() => this.copied = false, 1500); },
                   qr(){ this.$nextTick(()=>{ const el=document.getElementById('pm-qr'); if(!el) return; el.innerHTML=''; if(this.sel && (this.sel.type==='crypto'||this.sel.type==='upi') && this.sel.address && window.QRCode){ new QRCode(el,{text:this.sel.address,width:150,height:150,correctLevel:QRCode.CorrectLevel.M}); } }); } }"
         x-effect="qr()">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1"><i class="fa-solid fa-arrow-down text-emerald-600 mr-1"></i> Deposit Funds</h2>
            <p class="text-sm text-gray-500 mb-3">Choose where to deposit, pick a method, send the funds, then upload your slip.</p>

            {{-- Deposit destination --}}
            <div class="grid grid-cols-3 gap-2 mb-5 text-center">
                <button type="button" @click="purpose='fund';currency='USD'" :class="purpose==='fund' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 text-gray-500'" class="py-2.5 rounded-xl border text-xs font-semibold leading-tight">
                    <i class="fa-solid fa-layer-group"></i><br>Mutual Fund<br><span class="text-[10px] opacity-70">USD</span>
                </button>
                <button type="button" @click="purpose='spot';currency='USD'" :class="purpose==='spot'&&currency==='USD' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-500'" class="py-2.5 rounded-xl border text-xs font-semibold leading-tight">
                    <i class="fa-solid fa-arrow-trend-up"></i><br>Spot · US/Global<br><span class="text-[10px] opacity-70">USD</span>
                </button>
                <button type="button" @click="purpose='spot';currency='INR'" :class="purpose==='spot'&&currency==='INR' ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-gray-200 text-gray-500'" class="py-2.5 rounded-xl border text-xs font-semibold leading-tight">
                    <i class="fa-solid fa-arrow-trend-up"></i><br>Spot · India<br><span class="text-[10px] opacity-70">INR</span>
                </button>
            </div>

            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
            @endif

            {{-- Step 1: pick a method --}}
            <div x-show="!sel">
                @forelse ($methods as $m)
                    <button type="button" @click="sel = methods[{{ $loop->index }}]"
                            class="w-full flex items-center gap-3 p-4 mb-2 rounded-xl border border-gray-200 hover:border-emerald-400 hover:bg-emerald-50/40 text-left">
                        <span class="w-10 h-10 rounded-lg grid place-items-center text-white shrink-0 {{ $m->type==='crypto' ? 'bg-amber-500' : ($m->type==='upi' ? 'bg-purple-500' : 'bg-blue-600') }}">
                            <i class="fa-solid {{ $m->type==='crypto' ? 'fa-coins' : ($m->type==='upi' ? 'fa-mobile-screen' : 'fa-building-columns') }}"></i>
                        </span>
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">{{ $m->name }}{{ $m->type==='crypto' && $m->network ? ' · '.$m->network : '' }}</p>
                            <p class="text-xs text-gray-400">{{ $m->currency }}</p>
                        </div>
                        <i class="fa-solid fa-chevron-right text-gray-300"></i>
                    </button>
                @empty
                    <p class="text-sm text-gray-400 text-center py-6">No deposit methods configured yet. Please contact support.</p>
                @endforelse
            </div>

            {{-- Step 2: address + form --}}
            <div x-show="sel" x-cloak>
                <button type="button" @click="sel = null" class="text-sm text-emerald-600 mb-3"><i class="fa-solid fa-chevron-left"></i> Back</button>
                <div class="flex items-center gap-2 mb-3"><span class="font-semibold text-gray-900" x-text="sel?.label"></span></div>

                <div class="bg-gray-50 rounded-xl p-4 mb-4">
                    {{-- QR for crypto / UPI --}}
                    <div x-show="sel && (sel.type==='crypto' || sel.type==='upi')" class="flex justify-center mb-3">
                        <div id="pm-qr" class="bg-white p-2 rounded-lg border border-gray-200"></div>
                    </div>

                    {{-- Crypto / UPI: address + copy --}}
                    <template x-if="sel && sel.type!=='bank'">
                        <div>
                            <p class="text-xs text-gray-500 mb-1" x-text="sel?.type==='upi' ? 'UPI ID' : 'Wallet address'"></p>
                            <div class="flex items-center gap-2">
                                <code class="text-sm text-gray-800 break-all flex-1" x-text="sel?.address || '—'"></code>
                                <button type="button" @click="copy(sel?.address)" class="w-8 h-8 rounded-md bg-emerald-600 text-white grid place-items-center shrink-0" title="Copy"><i class="fa-regular fa-copy"></i></button>
                            </div>
                            <p class="text-xs text-amber-600 mt-2" x-show="sel?.type==='crypto' && sel?.network" x-text="'Send only on the ' + (sel?.network||'') + ' network.'"></p>
                            <div class="flex items-center gap-2 mt-2" x-show="sel?.type==='upi' && sel?.details?.provider">
                                <p class="text-xs text-gray-500 flex-1">Provider: <span class="text-gray-800 font-medium" x-text="sel?.details?.provider||''"></span></p>
                                <button type="button" @click="copy(sel?.details?.provider)" class="w-7 h-7 rounded-md bg-gray-200 text-gray-700 grid place-items-center shrink-0" title="Copy"><i class="fa-regular fa-copy text-xs"></i></button>
                            </div>
                        </div>
                    </template>

                    {{-- Bank: structured details, each with a copy button --}}
                    <template x-if="sel && sel.type==='bank'">
                        <div class="text-sm space-y-2.5">
                            <template x-for="row in [
                                {k:'Account name', v: sel?.details?.account_name},
                                {k:'Account number', v: sel?.details?.account_number},
                                {k:'Bank', v: sel?.details?.bank_name},
                                {k:'IFSC', v: sel?.details?.ifsc},
                            ]" :key="row.k">
                                <div class="flex items-center justify-between gap-2" x-show="row.v">
                                    <div class="min-w-0">
                                        <p class="text-xs text-gray-500" x-text="row.k"></p>
                                        <p class="font-medium text-gray-800 break-all" x-text="row.v || '—'"></p>
                                    </div>
                                    <button type="button" @click="copy(row.v)" class="w-8 h-8 rounded-md bg-emerald-600 text-white grid place-items-center shrink-0" title="Copy"><i class="fa-regular fa-copy"></i></button>
                                </div>
                            </template>
                        </div>
                    </template>

                    <p class="text-xs text-gray-400 mt-2" x-text="sel?.instructions"></p>
                    <p x-show="copied" x-cloak x-transition class="text-xs text-emerald-600 font-medium mt-2"><i class="fa-solid fa-check mr-1"></i> Copied to clipboard</p>
                </div>

                <form method="POST" action="{{ route('client.deposit.store') }}" enctype="multipart/form-data" class="space-y-4 text-sm">
                    @csrf
                    <input type="hidden" name="method" :value="sel?.label">
                    <input type="hidden" name="purpose" :value="purpose">
                    <input type="hidden" name="currency" :value="currency">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div><label class="block text-gray-700 mb-1">Amount (USD)</label><input type="number" step="0.01" min="1" name="amount" required class="w-full border-gray-300 rounded-md" placeholder="0.00"></div>
                        <div><label class="block text-gray-700 mb-1">Slip (image or PDF)</label><input type="file" name="slip" accept=".jpg,.jpeg,.png,.pdf" required class="w-full text-xs file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-gray-100 file:text-gray-700"></div>
                    </div>
                    <div><label class="block text-gray-700 mb-1">Note (optional)</label><textarea name="note" rows="2" maxlength="1000" class="w-full border-gray-300 rounded-md" placeholder="Reference, transaction hash…"></textarea></div>
                    <button class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 rounded-lg"><i class="fa-solid fa-paper-plane mr-1"></i> Submit deposit</button>
                </form>
            </div>
        </div>

        {{-- Recent deposits --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 mt-6">
            <h3 class="font-semibold text-gray-900 mb-3">Recent deposits</h3>
            <div class="divide-y divide-gray-100 text-sm">
                @forelse ($recent as $d)
                    <div class="py-3 flex items-center justify-between">
                        <div><p class="font-medium text-gray-900">{{ $money($d->amount) }}</p><p class="text-xs text-gray-400">{{ $d->method ?? '—' }} · {{ $d->created_at->format('d M Y') }}</p></div>
                        @php $b = ['pending'=>'bg-amber-100 text-amber-800','approved'=>'bg-emerald-100 text-emerald-800','rejected'=>'bg-red-100 text-red-700'][$d->status] ?? 'bg-gray-100'; @endphp
                        <span class="text-xs px-2.5 py-1 rounded-full capitalize {{ $b }}">{{ $d->status }}</span>
                    </div>
                @empty
                    <p class="py-6 text-center text-gray-400">No deposits yet.</p>
                @endforelse
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs@gh-pages/qrcode.min.js"></script>
</x-client-layout>
