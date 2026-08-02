<x-admin-layout title="SMS (Notify.lk)">
    <div class="max-w-2xl space-y-6">
        @if (session('status'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3 break-words">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
        @endif

        {{-- Status / balance --}}
        <div class="bg-white shadow rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-1"><i class="fa-solid fa-comment-sms text-emerald-600"></i> Notify.lk SMS</h2>
                    <p class="text-sm text-gray-500">Send an SMS to your team when a client submits a <strong>Mutual Fund</strong> deposit, withdrawal or KYC. Each message includes the client ID, name and amount.</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">{{ $enabled ? 'ON' : 'OFF' }}</span>
            </div>
            @if (! $configured)
                <div class="mt-4 bg-amber-50 border border-amber-200 text-amber-700 text-sm rounded-lg p-3">Enter your Notify.lk <strong>User ID</strong> and <strong>API key</strong> below to start sending.</div>
            @elseif (is_array($balance) && isset($balance['data']['acc_balance']))
                <div class="mt-4 text-sm text-gray-600">SMS balance: <strong class="text-gray-900">{{ $balance['data']['acc_balance'] }}</strong></div>
            @elseif ($balance !== null)
                <div class="mt-4 text-xs text-gray-400">Balance: {{ \Illuminate\Support\Str::limit(json_encode($balance), 120) }}</div>
            @endif
        </div>

        {{-- Settings --}}
        <form method="POST" action="{{ route('admin.settings.notify.update') }}" class="bg-white shadow rounded-xl p-6 space-y-5 text-sm">
            @csrf

            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="notify_enabled" value="1" @checked($enabled) class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 w-5 h-5">
                <span class="font-semibold text-gray-800">Enable SMS notifications</span>
            </label>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 mb-1">User ID</label>
                    <input name="notify_user_id" value="{{ old('notify_user_id', $userId) }}" class="w-full border-gray-300 rounded-md" placeholder="e.g. 29207">
                </div>
                <div>
                    <label class="block text-gray-700 mb-1">Sender ID</label>
                    <input name="notify_sender_id" value="{{ old('notify_sender_id', $senderId) }}" class="w-full border-gray-300 rounded-md" placeholder="e.g. NotifyDEMO">
                </div>
            </div>

            <div>
                <label class="block text-gray-700 mb-1">API key</label>
                <input name="notify_api_key" type="text" autocomplete="off" class="w-full border-gray-300 rounded-md" placeholder="{{ $apiKey ? '•••••••• (saved — leave blank to keep)' : 'Paste your Notify.lk API key' }}">
                <p class="text-[11px] text-gray-400 mt-1">Stored securely in the database, not in code. Leave blank to keep the current key.</p>
            </div>

            <div>
                <label class="block text-gray-700 mb-1">Recipient numbers</label>
                <textarea name="notify_numbers" rows="3" class="w-full border-gray-300 rounded-md font-mono text-xs" placeholder="94779895511, 94713900050, 94774275979">{{ old('notify_numbers', $numbers) }}</textarea>
                <p class="text-[11px] text-gray-400 mt-1">One or more numbers with country code (no +). Separate with comma, space or new line.</p>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <p class="text-gray-700 font-medium mb-2">Send an SMS when a client submits…</p>
                <div class="space-y-2">
                    <label class="flex items-center gap-3"><input type="checkbox" name="notify_on_deposit" value="1" @checked($onDeposit) class="rounded border-gray-300 text-emerald-600"> <span>Deposit request</span></label>
                    <label class="flex items-center gap-3"><input type="checkbox" name="notify_on_withdrawal" value="1" @checked($onWithdrawal) class="rounded border-gray-300 text-emerald-600"> <span>Withdrawal request</span></label>
                    <label class="flex items-center gap-3"><input type="checkbox" name="notify_on_kyc" value="1" @checked($onKyc) class="rounded border-gray-300 text-emerald-600"> <span>KYC document submission</span></label>
                </div>
            </div>

            <button class="px-4 py-2 bg-emerald-600 text-white rounded-md">Save SMS settings</button>
        </form>

        {{-- Test send --}}
        <form method="POST" action="{{ route('admin.settings.notify.test') }}" class="bg-white shadow rounded-xl p-6 space-y-4 text-sm">
            @csrf
            <div>
                <h2 class="text-base font-semibold text-gray-900 mb-1">Send a test SMS</h2>
                <p class="text-gray-500">Sends immediately using the saved credentials (save first if you just changed them).</p>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 mb-1">Phone number</label>
                    <input name="test_phone" value="{{ old('test_phone') }}" required class="w-full border-gray-300 rounded-md" placeholder="94779895511">
                </div>
                <div>
                    <label class="block text-gray-700 mb-1">Message (optional)</label>
                    <input name="test_message" value="{{ old('test_message') }}" class="w-full border-gray-300 rounded-md" placeholder="Test message…">
                </div>
            </div>
            <button class="px-4 py-2 bg-gray-800 text-white rounded-md"><i class="fa-solid fa-paper-plane"></i> Send test</button>
        </form>
    </div>
</x-admin-layout>
