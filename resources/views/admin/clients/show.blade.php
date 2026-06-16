<x-admin-layout title="Client · {{ $client->name }}">
    @php $money = fn ($n) => '$' . number_format((float) $n, 2); @endphp

    <a href="{{ route('admin.clients.index') }}" class="text-sm text-gray-500 hover:text-gray-700"><i class="fa-solid fa-arrow-left"></i> Back to clients</a>

    {{-- Balance cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
        <div class="bg-white shadow rounded-xl p-5">
            <p class="text-xs text-gray-500"><i class="fa-solid fa-wallet text-gray-400 mr-1"></i> Account balance</p>
            <p class="text-2xl font-bold text-gray-900">{{ $money($client->currentBalance()) }}</p>
            <p class="text-[11px] text-gray-400">Deposits + profit − withdrawals</p>
        </div>
        <div class="bg-white shadow rounded-xl p-5">
            <p class="text-xs text-gray-500"><i class="fa-solid fa-arrow-down text-gray-400 mr-1"></i> Total deposited</p>
            <p class="text-2xl font-bold text-gray-900">{{ $money($client->totalDeposited()) }}</p>
        </div>
        <div class="bg-white shadow rounded-xl p-5">
            <p class="text-xs text-gray-500"><i class="fa-solid fa-chart-line text-gray-400 mr-1"></i> Total profit</p>
            <p class="text-2xl font-bold text-emerald-600">{{ $money($client->totalProfit()) }}</p>
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
                    <div><label class="block text-gray-700">Country</label><input name="country" value="{{ old('country',$client->country) }}" class="mt-1 w-full border-gray-300 rounded-md"></div>
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
                        @foreach (['pending','active','suspended'] as $s)<option value="{{ $s }}" @selected($client->status===$s)>{{ ucfirst($s) }}</option>@endforeach
                    </select>
                </div>
                <button class="px-4 py-2 bg-emerald-600 text-white rounded-md w-full">Save changes</button>
            </form>

            <form method="POST" action="{{ route('admin.clients.destroy', $client) }}" class="mt-3" onsubmit="return confirm('Delete this client and all their data? This cannot be undone.')">
                @csrf @method('DELETE')
                <button class="px-4 py-2 bg-red-600 text-white rounded-md w-full text-sm"><i class="fa-solid fa-trash"></i> Delete client</button>
            </form>
        </div>

        {{-- Right column --}}
        <div class="lg:col-span-2 space-y-6">
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

                {{-- Upload on behalf of the client --}}
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
            </div>

            {{-- Recent transactions --}}
            <div class="bg-white shadow rounded-xl p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Recent transactions</h3>
                <table class="min-w-full text-sm">
                    <thead class="text-gray-500 text-left"><tr><th class="py-2">Date</th><th>Type</th><th>Amount</th><th>Balance</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($client->transactions as $t)
                            <tr><td class="py-2 text-gray-400">{{ $t->created_at->format('d M Y') }}</td>
                                <td>{{ ucfirst($t->type) }}</td>
                                <td class="{{ $t->amount < 0 ? 'text-red-600' : 'text-green-600' }}">{{ number_format((float)$t->amount,2) }}</td>
                                <td>{{ number_format((float)$t->balance_after,2) }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-center text-gray-400">No transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
