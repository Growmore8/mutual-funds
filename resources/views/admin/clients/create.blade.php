<x-admin-layout title="New Client">
    <a href="{{ route('admin.clients.index') }}" class="text-sm text-gray-500 hover:text-gray-700"><i class="fa-solid fa-arrow-left"></i> Back to clients</a>

    <div class="max-w-2xl bg-white shadow rounded-xl p-6 mt-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Create a client</h2>
        <p class="text-sm text-gray-500 mb-5">The client can log in with the email + password you set here. Email OTP is skipped for admin-created accounts.</p>

        @if ($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.clients.store') }}" class="space-y-4 text-sm">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div><label class="block text-gray-700 mb-1">Full name</label><input name="name" value="{{ old('name') }}" required class="w-full border-gray-300 rounded-md"></div>
                <div><label class="block text-gray-700 mb-1">Email (login username)</label><input type="email" name="email" value="{{ old('email') }}" required class="w-full border-gray-300 rounded-md"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div><label class="block text-gray-700 mb-1">Password</label><input type="text" name="password" required minlength="8" class="w-full border-gray-300 rounded-md" placeholder="min 8 characters"></div>
                <div><label class="block text-gray-700 mb-1">Phone</label><input name="phone" value="{{ old('phone') }}" class="w-full border-gray-300 rounded-md"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div><label class="block text-gray-700 mb-1">Country</label><x-country-select name="country" :value="old('country')" class="w-full border-gray-300 rounded-md" /></div>
                <div><label class="block text-gray-700 mb-1">Address</label><input name="address" value="{{ old('address') }}" class="w-full border-gray-300 rounded-md"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-gray-700 mb-1">Account type</label>
                    <select name="account_type_id" class="w-full border-gray-300 rounded-md">
                        <option value="">— none —</option>
                        @foreach ($accountTypes as $at)<option value="{{ $at->id }}" @selected(old('account_type_id')==$at->id)>{{ $at->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 mb-1">Live ID (pool account)</label>
                    <select name="pool_account_id" class="w-full border-gray-300 rounded-md">
                        <option value="">— unassigned —</option>
                        @foreach ($pools as $p)<option value="{{ $p->id }}" @selected(old('pool_account_id')==$p->id)>{{ $p->account_ref }} {{ $p->name ? '· '.$p->name : '' }}</option>@endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-gray-700 mb-1">Account status</label>
                <select name="status" class="w-full border-gray-300 rounded-md">
                    @foreach (['active','pending','suspended'] as $s)<option value="{{ $s }}" @selected(old('status','active')===$s)>{{ ucfirst($s) }}</option>@endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">KYC starts as “not submitted” — upload documents (here or by the client) then approve.</p>
            </div>

            <button class="px-4 py-2 bg-emerald-600 text-white rounded-md font-medium"><i class="fa-solid fa-user-plus mr-1"></i> Create client</button>
        </form>
    </div>
</x-admin-layout>
