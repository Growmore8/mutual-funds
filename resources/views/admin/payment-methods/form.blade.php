<x-admin-layout title="{{ $method->exists ? 'Edit' : 'New' }} Payment Method">
    <a href="{{ route('admin.payment-methods.index') }}" class="text-sm text-gray-500">&larr; Back</a>
    <div class="bg-white shadow rounded-xl p-6 mt-4 max-w-2xl">
        @php
            $f = fn ($k, $d = '') => old($k, $method->$k ?? $d);
            $d = fn ($k, $def = '') => old($k, $method->details[$k] ?? $def);
        @endphp
        <form method="POST" action="{{ $method->exists ? route('admin.payment-methods.update',$method) : route('admin.payment-methods.store') }}" class="space-y-4"
              x-data="{ type: '{{ $f('type','crypto') }}' }">
            @csrf
            @if ($method->exists) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Display name</label>
                    <input name="name" value="{{ $f('name') }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="e.g. USDT / Company Bank" required>
                    @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Type</label>
                    <select name="type" x-model="type" class="mt-1 w-full border-gray-300 rounded-md">
                        @foreach (['crypto'=>'Crypto','upi'=>'UPI','bank'=>'Bank'] as $val=>$lbl)
                            <option value="{{ $val }}" @selected($f('type','crypto')===$val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Crypto --}}
            <div x-show="type==='crypto'" class="space-y-4 border-t pt-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Network</label>
                        <select name="network" class="mt-1 w-full border-gray-300 rounded-md">
                            @foreach (['BEP20','ERC20','TRC20'] as $n)
                                <option value="{{ $n }}" @selected($f('network')===$n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="block text-sm font-medium text-gray-700">Currency</label><input name="currency" value="{{ $f('currency','USDT') }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="USDT"></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700">Wallet address</label><input name="wallet" value="{{ $d('wallet', $f('address')) }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="0x… / T…"></div>
                <p class="text-xs text-gray-400">Clients see this address + a QR code on the deposit page.</p>
            </div>

            {{-- UPI --}}
            <div x-show="type==='upi'" x-cloak class="space-y-4 border-t pt-4">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700">Provider</label><input name="provider" value="{{ $d('provider') }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="GPay / PhonePe / Paytm"></div>
                    <div><label class="block text-sm font-medium text-gray-700">UPI ID</label><input name="upi_id" value="{{ $d('upi_id', $f('address')) }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="name@bank"></div>
                </div>
                <p class="text-xs text-gray-400">Clients see the UPI ID + a QR code.</p>
            </div>

            {{-- Bank --}}
            <div x-show="type==='bank'" x-cloak class="space-y-4 border-t pt-4">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700">Account name</label><input name="account_name" value="{{ $d('account_name') }}" class="mt-1 w-full border-gray-300 rounded-md"></div>
                    <div><label class="block text-sm font-medium text-gray-700">Account number</label><input name="account_number" value="{{ $d('account_number', $f('address')) }}" class="mt-1 w-full border-gray-300 rounded-md"></div>
                    <div><label class="block text-sm font-medium text-gray-700">Bank name</label><input name="bank_name" value="{{ $d('bank_name') }}" class="mt-1 w-full border-gray-300 rounded-md"></div>
                    <div><label class="block text-sm font-medium text-gray-700">IFSC code</label><input name="ifsc" value="{{ $d('ifsc') }}" class="mt-1 w-full border-gray-300 rounded-md"></div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Instructions (optional)</label>
                <textarea name="instructions" rows="2" class="mt-1 w-full border-gray-300 rounded-md">{{ $f('instructions') }}</textarea>
            </div>
            <div class="flex items-center gap-6">
                <div><label class="block text-sm font-medium text-gray-700">Sort order</label><input type="number" name="sort_order" value="{{ $f('sort_order',0) }}" class="mt-1 w-32 border-gray-300 rounded-md" required></div>
                <label class="inline-flex items-center gap-2 text-sm mt-5"><input type="checkbox" name="is_active" value="1" @checked($f('is_active',true)) class="rounded"> Active</label>
            </div>
            <div class="pt-2"><button class="px-5 py-2 bg-emerald-600 text-white rounded-md">{{ $method->exists ? 'Save changes' : 'Create' }}</button></div>
        </form>
    </div>
</x-admin-layout>
