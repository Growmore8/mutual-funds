<x-admin-layout title="{{ $method->exists ? 'Edit' : 'New' }} Payment Method">
    <a href="{{ route('admin.payment-methods.index') }}" class="text-sm text-gray-500">&larr; Back</a>
    <div class="bg-white shadow rounded-xl p-6 mt-4 max-w-2xl">
        <form method="POST" action="{{ $method->exists ? route('admin.payment-methods.update',$method) : route('admin.payment-methods.store') }}" class="space-y-4">
            @csrf
            @if ($method->exists) @method('PUT') @endif
            @php $f = fn($k,$d='') => old($k, $method->$k ?? $d); @endphp
            <div>
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <input name="name" value="{{ $f('name') }}" class="mt-1 w-full border-gray-300 rounded-md" required>
                @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Type</label>
                    <select name="type" class="mt-1 w-full border-gray-300 rounded-md">
                        @foreach (['crypto'=>'Crypto','bank'=>'Bank','upi'=>'UPI','card'=>'Card','ewallet'=>'E-wallet'] as $val=>$lbl)
                            <option value="{{ $val }}" @selected($f('type','crypto')===$val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="block text-sm font-medium text-gray-700">Network (crypto)</label><input name="network" value="{{ $f('network') }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="BEP20 / ERC20 / TRC20"></div>
                <div><label class="block text-sm font-medium text-gray-700">Currency</label><input name="currency" value="{{ $f('currency') }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="USDT / USD / INR"></div>
                <div><label class="block text-sm font-medium text-gray-700">Wallet / account / UPI ID</label><input name="address" value="{{ $f('address') }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Address shown to clients"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Instructions (optional)</label>
                <textarea name="instructions" rows="3" class="mt-1 w-full border-gray-300 rounded-md">{{ $f('instructions') }}</textarea>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">Sort order</label><input type="number" name="sort_order" value="{{ $f('sort_order',0) }}" class="mt-1 w-full border-gray-300 rounded-md" required></div>
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($f('is_active',true)) class="rounded"> Active</label>
            <div class="pt-2"><button class="px-5 py-2 bg-emerald-600 text-white rounded-md">{{ $method->exists ? 'Save changes' : 'Create' }}</button></div>
        </form>
    </div>
</x-admin-layout>
