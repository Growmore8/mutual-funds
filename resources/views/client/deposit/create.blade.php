<x-client-layout title="Deposit">
    @php
        $money = fn ($n) => '$' . number_format((float) $n, 2);
        $methodsJson = $methods->map(fn ($m) => [
            'label' => trim($m->name . ($m->network ? ' · ' . $m->network : '')),
            'type' => $m->type,
            'network' => $m->network,
            'currency' => $m->currency,
            'address' => $m->address,
            'instructions' => $m->instructions,
        ])->values();
    @endphp

    <div class="max-w-2xl mx-auto" x-data="{ sel: null, methods: {{ Illuminate\Support\Js::from($methodsJson) }} }">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1"><i class="fa-solid fa-arrow-down text-emerald-600 mr-1"></i> Deposit Funds</h2>
            <p class="text-sm text-gray-500 mb-5">Choose a method, send the funds, then upload your slip. Your balance updates once an admin approves.</p>

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
                            <p class="font-medium text-gray-900">{{ $m->name }}{{ $m->network ? ' · '.$m->network : '' }}</p>
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
                    <p class="text-xs text-gray-500 mb-1">Send to this address</p>
                    <div class="flex items-center gap-2">
                        <code class="text-sm text-gray-800 break-all flex-1" x-text="sel?.address || '—'"></code>
                        <button type="button" @click="navigator.clipboard.writeText(sel?.address || '')" class="w-8 h-8 rounded-md bg-emerald-600 text-white grid place-items-center shrink-0" title="Copy"><i class="fa-regular fa-copy"></i></button>
                    </div>
                    <p class="text-xs text-amber-600 mt-2" x-show="sel?.network" x-text="'Send only on the ' + (sel?.network||'') + ' network.'"></p>
                    <p class="text-xs text-gray-400 mt-1" x-text="sel?.instructions"></p>
                </div>

                <form method="POST" action="{{ route('client.deposit.store') }}" enctype="multipart/form-data" class="space-y-4 text-sm">
                    @csrf
                    <input type="hidden" name="method" :value="sel?.label">
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
</x-client-layout>
