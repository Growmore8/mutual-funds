<x-admin-layout title="{{ $type->exists ? 'Edit' : 'New' }} Account Type">
    <a href="{{ route('admin.account-types.index') }}" class="text-sm text-gray-500">&larr; Back</a>
    <div class="bg-white shadow rounded-xl p-6 mt-4 max-w-2xl">
        <form method="POST" action="{{ $type->exists ? route('admin.account-types.update',$type) : route('admin.account-types.store') }}" class="space-y-4">
            @csrf
            @if ($type->exists) @method('PUT') @endif

            @php $f = fn($k,$d='') => old($k, $type->$k ?? $d); @endphp
            <div>
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <input name="name" value="{{ $f('name') }}" class="mt-1 w-full border-gray-300 rounded-md" required>
                @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" rows="2" class="mt-1 w-full border-gray-300 rounded-md">{{ $f('description') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700">Min deposit ($)</label><input type="number" step="0.01" name="min_deposit" value="{{ $f('min_deposit',0) }}" class="mt-1 w-full border-gray-300 rounded-md" required></div>
                <div><label class="block text-sm font-medium text-gray-700">Max deposit ($, optional)</label><input type="number" step="0.01" name="max_deposit" value="{{ $f('max_deposit') }}" class="mt-1 w-full border-gray-300 rounded-md"></div>
                <div><label class="block text-sm font-medium text-gray-700">Pool amount ($)</label><input type="number" step="0.01" name="pool_amount" value="{{ $f('pool_amount',0) }}" class="mt-1 w-full border-gray-300 rounded-md" required><p class="text-xs text-gray-400 mt-1">Profit share = invested ÷ pool amount.</p></div>
                <div><label class="block text-sm font-medium text-gray-700">Profit share (%)</label><input type="number" step="0.01" name="profit_share_pct" value="{{ $f('profit_share_pct',100) }}" class="mt-1 w-full border-gray-300 rounded-md" required></div>
                <div><label class="block text-sm font-medium text-gray-700">Management fee (%)</label><input type="number" step="0.01" name="management_fee_pct" value="{{ $f('management_fee_pct',0) }}" class="mt-1 w-full border-gray-300 rounded-md" required></div>
                <div><label class="block text-sm font-medium text-gray-700">Lock-in (months)</label><input type="number" name="lock_in_months" value="{{ $f('lock_in_months',0) }}" class="mt-1 w-full border-gray-300 rounded-md" required></div>
                <div><label class="block text-sm font-medium text-gray-700">Sort order</label><input type="number" name="sort_order" value="{{ $f('sort_order',0) }}" class="mt-1 w-full border-gray-300 rounded-md" required></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Features (one per line)</label>
                <textarea name="features_text" rows="3" class="mt-1 w-full border-gray-300 rounded-md">{{ old('features_text', implode("\n", (array) ($type->features ?? []))) }}</textarea>
            </div>
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($f('is_active',true)) class="rounded"> Active</label>
            <div class="pt-2"><button class="px-5 py-2 bg-emerald-600 text-white rounded-md">{{ $type->exists ? 'Save changes' : 'Create' }}</button></div>
        </form>
    </div>
</x-admin-layout>
