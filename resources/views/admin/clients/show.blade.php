<x-admin-layout title="Client · {{ $client->name }}">
    @php $money = fn ($n) => '$' . number_format((float) $n, 2); @endphp

    <a href="{{ route('admin.clients.index') }}" class="text-sm text-gray-500 hover:text-gray-700"><i class="fa-solid fa-arrow-left"></i> Back to clients</a>

    {{-- Balance cards --}}
    @php $cpnl = $client->runningPnl(); @endphp
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
        <div class="bg-white shadow rounded-xl p-5">
            <p class="text-xs text-gray-500"><i class="fa-solid fa-wallet text-gray-400 mr-1"></i> Capital (Balance)</p>
            <p class="text-2xl font-bold text-gray-900">{{ $money($client->totalDeposited()) }}</p>
            <p class="text-[11px] text-gray-400">Principal · all accounts</p>
        </div>
        <div class="bg-white shadow rounded-xl p-5">
            <p class="text-xs text-gray-500"><i class="fa-solid fa-chart-line text-gray-400 mr-1"></i> Running PnL</p>
            <p class="text-2xl font-bold {{ $cpnl < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ ($cpnl < 0 ? '-' : '+') . $money(abs($cpnl)) }}</p>
            <p class="text-[11px] text-gray-400">Profit/loss after payouts</p>
        </div>
        <div class="bg-white shadow rounded-xl p-5">
            <p class="text-xs text-gray-500"><i class="fa-solid fa-money-bill-wave text-gray-400 mr-1"></i> Withdrawable</p>
            <p class="text-2xl font-bold text-emerald-600">{{ $money($client->availableToWithdraw()) }}</p>
            <p class="text-[11px] text-gray-400">Positive PnL only</p>
        </div>
    </div>

    {{-- Per-product overview: Mutual Fund · Spot (single USD) --}}
    @php
        $mfCapital = (float) $client->totalDeposited();
        $mfBalance = $mfCapital + (float) $cpnl;
        $usBal = (float) $spotUsd->balance;
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
        <div class="bg-white shadow rounded-xl p-5 border-l-4 border-emerald-500">
            <p class="text-xs font-semibold text-emerald-700"><i class="fa-solid fa-layer-group mr-1"></i> Mutual Fund (USD)</p>
            <div class="flex justify-between mt-2">
                <div><p class="text-[11px] text-gray-400">Capital</p><p class="text-lg font-bold text-gray-900">{{ $money($mfCapital) }}</p></div>
                <div class="text-right"><p class="text-[11px] text-gray-400">Balance</p><p class="text-lg font-bold text-gray-900">{{ $money($mfBalance) }}</p></div>
            </div>
        </div>
        <div class="bg-white shadow rounded-xl p-5 border-l-4 border-blue-500">
            <p class="text-xs font-semibold text-blue-700"><i class="fa-solid fa-arrow-trend-up mr-1"></i> Spot Trading (USD)</p>
            <div class="flex justify-between mt-2">
                <div><p class="text-[11px] text-gray-400">Wallet</p><p class="text-lg font-bold text-gray-900">${{ number_format($usBal, 2) }}</p></div>
                <div class="text-right"><p class="text-[11px] text-gray-400">P&L</p><p class="text-lg font-bold {{ $spotPnl < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ ($spotPnl < 0 ? '-' : '+') }}${{ number_format(abs($spotPnl), 2) }}</p></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        {{-- Edit client --}}
        <div class="bg-white shadow rounded-xl p-6 lg:col-span-1">
            <h3 class="font-semibold text-gray-900 mb-4">Edit client</h3>
            <form method="POST" action="{{ route('admin.clients.update', $client) }}" class="space-y-3 text-sm">
                @csrf @method('PATCH')
                <div><label class="block text-gray-700">Name</label><input name="name" value="{{ old('name',$client->name) }}" class="mt-1 w-full border-gray-300 rounded-md" required></div>
                <div><label class="block text-gray-700">Email</label><input type="email" name="email" value="{{ old('email',$client->email) }}" class="mt-1 w-full border-gray-300 rounded-md" required></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="block text-gray-700">Phone</label><input name="phone" value="{{ old('phone',$client->phone) }}" class="mt-1 w-full border-gray-300 rounded-md"></div>
                    <div><label class="block text-gray-700">Country</label><x-country-select name="country" :value="old('country',$client->country)" class="mt-1 w-full border-gray-300 rounded-md" /></div>
                </div>
                <div><label class="block text-gray-700">Address</label><input name="address" value="{{ old('address',$client->address) }}" class="mt-1 w-full border-gray-300 rounded-md"></div>
                <div>
                    <label class="block text-gray-700">Account type</label>
                    <select name="account_type_id" class="mt-1 w-full border-gray-300 rounded-md">
                        <option value="">— none —</option>
                        @foreach ($accountTypes as $at)
                            <option value="{{ $at->id }}" @selected($client->account_type_id==$at->id)>{{ $at->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700">Live ID (pool account)</label>
                    <select name="pool_account_id" class="mt-1 w-full border-gray-300 rounded-md">
                        <option value="">— unassigned —</option>
                        @foreach ($pools as $p)
                            <option value="{{ $p->id }}" @selected($client->pool_account_id==$p->id)>{{ $p->account_ref }} {{ $p->name ? '· '.$p->name : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700">Account status</label>
                    <select name="status" class="mt-1 w-full border-gray-300 rounded-md">
                        @foreach (['pending','active','suspended','locked'] as $s)<option value="{{ $s }}" @selected($client->status===$s)>{{ ucfirst($s) }}</option>@endforeach
                    </select>
                </div>
                <label class="flex items-start gap-2 text-xs bg-amber-50 border border-amber-200 rounded-md p-2">
                    <input type="checkbox" name="plan_locked" value="1" @checked($client->plan_locked) class="rounded mt-0.5">
                    <span><span class="font-medium text-amber-800">Lock plan &amp; Live ID (manual)</span> — keep the chosen plan/pool fixed; the deposit-based auto-upgrade won't change it.</span>
                </label>
                <button class="px-4 py-2 bg-emerald-600 text-white rounded-md w-full">Save changes</button>
            </form>

            <form method="POST" action="{{ route('admin.clients.destroy', $client) }}" class="mt-3" onsubmit="return confirm('Delete this client and all their data? This cannot be undone.')">
                @csrf @method('DELETE')
                <button class="px-4 py-2 bg-red-600 text-white rounded-md w-full text-sm"><i class="fa-solid fa-trash"></i> Delete client</button>
            </form>
        </div>

        {{-- Right column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Fund accounts (per-account management) --}}
            <div class="bg-white shadow rounded-xl p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Fund accounts <span class="text-xs text-gray-400">({{ $client->fundAccounts->count() }})</span></h3>
                <div class="space-y-4">
                    @forelse ($client->fundAccounts as $acc)
                        @php $apnl = $acc->runningPnl(); @endphp
                        <div class="border border-gray-200 rounded-xl p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                <div class="font-medium text-gray-900">{{ $acc->label }} <span class="text-xs font-mono text-gray-400">{{ $acc->code() }}</span>
                                    @if($acc->is_primary)<span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">primary</span>@endif
                                    @unless($acc->active)<span class="text-[10px] px-1.5 py-0.5 rounded bg-red-100 text-red-700">deactivated</span>@elseif($acc->locked)<span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800">locked</span>@endif
                                </div>
                                <div class="text-xs text-gray-500">Capital <strong>{{ $money($acc->totalDeposited()) }}</strong> · PnL <strong class="{{ $apnl<0?'text-red-600':'text-emerald-600' }}">{{ ($apnl<0?'-':'+').$money(abs($apnl)) }}</strong> · Withdrawable <strong>{{ $money($acc->availableToWithdraw()) }}</strong></div>
                            </div>
                            <form method="POST" action="{{ route('admin.clients.account.update', [$client, $acc]) }}" class="grid grid-cols-2 gap-2 text-sm">
                                @csrf @method('PATCH')
                                <div><label class="block text-gray-600 text-xs">Label</label><input name="label" value="{{ $acc->label }}" class="mt-1 w-full border-gray-300 rounded-md"></div>
                                <div><label class="block text-gray-600 text-xs">Plan</label>
                                    <select name="account_type_id" class="mt-1 w-full border-gray-300 rounded-md">
                                        <option value="">— none —</option>
                                        @foreach ($accountTypes as $at)<option value="{{ $at->id }}" @selected($acc->account_type_id==$at->id)>{{ $at->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div><label class="block text-gray-600 text-xs">Live ID (pool)</label>
                                    <select name="pool_account_id" class="mt-1 w-full border-gray-300 rounded-md">
                                        <option value="">— unassigned —</option>
                                        @foreach ($pools as $p)<option value="{{ $p->id }}" @selected($acc->pool_account_id==$p->id)>{{ $p->account_ref }}</option>@endforeach
                                    </select>
                                </div>
                                <label class="flex items-center gap-2 text-xs mt-5"><input type="checkbox" name="plan_locked" value="1" @checked($acc->plan_locked) class="rounded"> Lock plan/pool</label>
                                <label class="flex items-center gap-2 text-xs mt-5"><input type="checkbox" name="locked" value="1" @checked($acc->locked) class="rounded text-amber-600"> Lock account (view-only)</label>
                                <label class="flex items-center gap-2 text-xs col-span-2"><input type="checkbox" name="active" value="1" @checked($acc->active) class="rounded text-emerald-600"> Account active (uncheck = deactivate)</label>
                                <div class="col-span-2 flex items-center justify-between gap-2">
                                    <button class="px-3 py-1.5 bg-emerald-600 text-white rounded-md text-sm">Save account</button>
                                </div>
                            </form>
                            @if ($client->fundAccounts->count() > 1)
                                <form method="POST" action="{{ route('admin.clients.account.destroy', [$client, $acc]) }}" class="mt-2 text-right"
                                      onsubmit="return confirm('Delete {{ $acc->label }} ({{ $acc->code() }}) and all its deposits, withdrawals, profit and history? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-600 hover:text-red-700 hover:underline"><i class="fa-solid fa-trash mr-1"></i> Delete this account</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No fund accounts.</p>
                    @endforelse
                </div>
            </div>

            {{-- Spot Trading (single USD wallet) --}}
            <div class="bg-white shadow rounded-xl p-6" id="spot">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-900"><i class="fa-solid fa-arrow-trend-up text-blue-500 mr-1"></i> Spot Trading (USD)
                        @unless($client->spot_active)<span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-red-100 text-red-700">deactivated</span>@elseif($client->spot_locked)<span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800">locked</span>@endif
                    </h3>
                    <form method="POST" action="{{ route('admin.spot.reset', $client) }}" onsubmit="return confirm('Reset spot account? Wallet set to 0 and all spot holdings/orders/trades deleted.')">
                        @csrf<button class="text-xs text-red-600 hover:underline">Reset spot account</button>
                    </form>
                </div>

                {{-- Spot access: lock (view-only) / deactivate — independent of mutual-fund accounts --}}
                <form method="POST" action="{{ route('admin.spot.access', $client) }}" class="flex flex-wrap items-center gap-4 mb-4 p-3 rounded-lg bg-gray-50 border border-gray-200 text-sm">
                    @csrf
                    <label class="flex items-center gap-2"><input type="checkbox" name="spot_locked" value="1" @checked($client->spot_locked) class="rounded text-amber-600"> Lock spot (view-only)</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="spot_active" value="1" @checked($client->spot_active) class="rounded text-emerald-600"> Spot active (uncheck = deactivate)</label>
                    <button class="px-3 py-1.5 bg-gray-800 text-white rounded-md text-xs">Apply spot access</button>
                </form>
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="rounded-lg border border-gray-200 p-3"><p class="text-xs text-gray-500">USD wallet</p><p class="text-xl font-bold text-gray-900">${{ number_format((float)$spotUsd->balance,2) }}</p></div>
                    <div class="rounded-lg border border-gray-200 p-3"><p class="text-xs text-gray-500">Unrealized P&L</p><p class="text-xl font-bold {{ $spotPnl < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ ($spotPnl<0?'-':'+') }}${{ number_format(abs($spotPnl),2) }}</p></div>
                </div>
                <form method="POST" action="{{ route('admin.spot.adjust', $client) }}" class="grid grid-cols-4 gap-2 text-sm mb-1">
                    @csrf
                    <input type="hidden" name="currency" value="USD">
                    <select name="direction" class="border-gray-300 rounded-md"><option value="credit">Credit +</option><option value="debit">Debit −</option></select>
                    <input type="number" step="0.01" name="amount" placeholder="USD 0.00" class="border-gray-300 rounded-md col-span-2" required>
                    <button class="px-3 py-2 bg-emerald-600 text-white rounded-md">Apply</button>
                </form>
                <p class="text-[11px] text-gray-400 mb-4">Wallet is USD. To fund from an INR amount, convert first or use Add transaction → “enter INR”.</p>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 mb-1">Holdings</p>
                        @forelse ($spotHoldings as $h)
                            <div class="flex justify-between text-xs py-1 border-b border-gray-100 last:border-0"><span>{{ $h->instrument->symbol }} ×{{ rtrim(rtrim((string)$h->qty,'0'),'.') }}</span><span class="text-gray-400">avg {{ $h->instrument->currencySymbol() }}{{ number_format((float)$h->avg_price,2) }}</span></div>
                        @empty <p class="text-xs text-gray-400 py-1">No holdings.</p> @endforelse
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 mb-1">Recent trades</p>
                        @forelse ($spotTrades as $t)
                            @php $isBuy = $t->buyer_id === $client->id; @endphp
                            <div class="flex justify-between items-center text-xs py-1 border-b border-gray-100 last:border-0">
                                <span class="{{ $isBuy?'text-emerald-600':'text-red-600' }}">{{ $isBuy?'Buy':'Sell' }} {{ $t->instrument->symbol }} ×{{ rtrim(rtrim((string)$t->qty,'0'),'.') }}</span>
                                <span class="flex items-center gap-2"><span class="text-gray-400">{{ $t->instrument->currencySymbol() }}{{ number_format((float)$t->price,2) }}</span>
                                <form method="POST" action="{{ route('admin.spot.trade.delete', $t) }}" onsubmit="return confirm('Delete this trade and reverse its effect?')">@csrf<button class="text-red-600 hover:underline">del</button></form></span>
                            </div>
                        @empty <p class="text-xs text-gray-400 py-1">No trades.</p> @endforelse
                    </div>
                </div>
            </div>

            {{-- Account requests --}}
            <div class="bg-white shadow rounded-xl p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Account requests</h3>
                @forelse ($client->accountRequests as $r)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0 text-sm">
                        <div>
                            <span class="font-medium">{{ $r->accountType->name ?? '—' }}</span>
                            <span class="text-gray-400">· {{ $r->created_at->format('d M Y') }}</span>
                            @if ($r->reason)<p class="text-xs text-gray-400">{{ $r->reason }}</p>@endif
                        </div>
                        @if ($r->status === 'pending')
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('admin.account-requests.approve', $r) }}">@csrf<button class="px-3 py-1.5 bg-emerald-600 text-white rounded-md">Approve</button></form>
                                <form method="POST" action="{{ route('admin.account-requests.reject', $r) }}">@csrf<button class="px-3 py-1.5 bg-red-600 text-white rounded-md">Reject</button></form>
                            </div>
                        @else
                            @php $b = ['approved'=>'bg-emerald-100 text-emerald-800','rejected'=>'bg-red-100 text-red-700'][$r->status]; @endphp
                            <span class="text-xs px-2.5 py-1 rounded-full capitalize {{ $b }}">{{ $r->status }}</span>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No additional-account requests.</p>
                @endforelse
            </div>

            {{-- KYC documents --}}
            <div class="bg-white shadow rounded-xl p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-900">KYC — National ID / Passport</h3>
                    @php $kc = ['not_submitted'=>'bg-gray-100 text-gray-600','submitted'=>'bg-amber-100 text-amber-800','approved'=>'bg-emerald-100 text-emerald-800','rejected'=>'bg-red-100 text-red-700'][$client->kyc_status] ?? 'bg-gray-100'; @endphp
                    <span class="text-xs px-2.5 py-1 rounded-full {{ $kc }}">{{ ucfirst(str_replace('_',' ',$client->kyc_status)) }}</span>
                </div>

                @if ($client->kyc_status !== 'approved')
                    <div class="flex gap-2 mb-4">
                        <form method="POST" action="{{ route('admin.clients.kyc.decision', $client) }}">@csrf
                            <input type="hidden" name="decision" value="approved">
                            <button class="px-3 py-1.5 bg-emerald-600 text-white rounded-md text-sm"><i class="fa-solid fa-check mr-1"></i> Approve KYC</button>
                        </form>
                        <form method="POST" action="{{ route('admin.clients.kyc.decision', $client) }}">@csrf
                            <input type="hidden" name="decision" value="rejected">
                            <button class="px-3 py-1.5 bg-red-600 text-white rounded-md text-sm">Reject</button>
                        </form>
                    </div>
                @endif

                @forelse ($client->kycDocuments as $doc)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0 text-sm">
                        <div>
                            <span class="font-medium text-gray-900">{{ $doc->document_number ?: 'ID document' }}</span>
                            <span class="text-gray-400">· {{ $doc->created_at->format('d M Y') }}</span>
                            <div class="mt-1 flex gap-3">
                                @if ($doc->front_path ?? $doc->file_path)
                                    <a href="{{ route('admin.kyc.file', [$doc, 'front']) }}" target="_blank" class="text-emerald-600 hover:underline"><i class="fa-regular fa-image"></i> Front</a>
                                @endif
                                @if ($doc->back_path)
                                    <a href="{{ route('admin.kyc.file', [$doc, 'back']) }}" target="_blank" class="text-emerald-600 hover:underline"><i class="fa-regular fa-image"></i> Back</a>
                                @endif
                            </div>
                        </div>
                        @php $kb = ['submitted'=>'bg-amber-100 text-amber-800','approved'=>'bg-emerald-100 text-emerald-800','rejected'=>'bg-red-100 text-red-700'][$doc->status] ?? 'bg-gray-100 text-gray-600'; @endphp
                        <span class="text-xs px-2.5 py-1 rounded-full capitalize {{ $kb }}">{{ $doc->status }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 mb-3">No documents uploaded.</p>
                @endforelse

                {{-- Upload on behalf of the client — only while KYC is not yet verified --}}
                @if ($client->kyc_status === 'approved')
                    <div class="mt-4 flex items-center gap-2 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                        <i class="fa-solid fa-circle-check"></i> KYC verified — documents above are available to view.
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.clients.kyc.upload', $client) }}" enctype="multipart/form-data" class="mt-4 space-y-3 text-sm border-t border-gray-100 pt-4">
                        @csrf
                        <p class="text-xs text-gray-500">Upload the client's National ID / Passport on their behalf.</p>
                        <div><label class="block text-gray-700 mb-1">Document number (optional)</label><input name="document_number" class="w-full border-gray-300 rounded-md"></div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="block text-gray-700 mb-1">Front</label><input type="file" name="front" accept=".jpg,.jpeg,.png,.pdf" required class="w-full text-xs"></div>
                            <div><label class="block text-gray-700 mb-1">Back</label><input type="file" name="back" accept=".jpg,.jpeg,.png,.pdf" required class="w-full text-xs"></div>
                        </div>
                        <button class="px-4 py-2 bg-gray-800 text-white rounded-md"><i class="fa-solid fa-upload mr-1"></i> Upload KYC</button>
                    </form>
                @endif
            </div>

            {{-- Recent transactions (Mutual Fund + Spot Trading) --}}
            <div class="bg-white shadow rounded-xl p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Recent transactions</h3>
                <table class="min-w-full text-sm">
                    <thead class="text-gray-500 text-left"><tr><th class="py-2">Date</th><th>Area</th><th>Detail</th><th class="text-right">Amount</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($activity as $a)
                            <tr>
                                <td class="py-2 text-gray-400 whitespace-nowrap">{{ $a->when->format('d M Y') }}</td>
                                <td><span class="text-xs px-2 py-0.5 rounded-full {{ $a->area==='Spot' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-800' }}">{{ $a->area }}</span></td>
                                <td class="text-gray-600">{{ $a->detail }}</td>
                                <td class="text-right font-medium {{ $a->amount < 0 ? 'text-red-600' : 'text-green-600' }}">{{ ($a->amount < 0 ? '-' : '+') }}${{ number_format(abs((float)$a->amount),2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-center text-gray-400">No transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
