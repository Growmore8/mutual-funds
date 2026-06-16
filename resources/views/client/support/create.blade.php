<x-client-layout title="New ticket">
    <div class="max-w-2xl">
        <a href="{{ route('support.index') }}" class="text-sm text-gray-500 hover:text-gray-700"><i class="fa-solid fa-arrow-left"></i> Back to tickets</a>

        <div class="bg-white shadow rounded-xl p-6 mt-4">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Open a support ticket</h2>
            <p class="text-sm text-gray-500 mb-5">Describe your issue and our team will get back to you.</p>

            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('support.store') }}" class="space-y-4 text-sm">
                @csrf
                <div>
                    <label class="block text-gray-700 mb-1">Subject</label>
                    <input name="subject" value="{{ old('subject') }}" required maxlength="150"
                           class="w-full border-gray-300 rounded-md" placeholder="e.g. Withdrawal not received">
                </div>
                <div>
                    <label class="block text-gray-700 mb-1">Category</label>
                    <select name="category" class="w-full border-gray-300 rounded-md">
                        <option value="general">General</option>
                        <option value="deposit">Deposit</option>
                        <option value="withdrawal">Withdrawal</option>
                        <option value="kyc">KYC / Verification</option>
                        <option value="technical">Technical</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 mb-1">Message</label>
                    <textarea name="body" rows="6" required maxlength="5000"
                              class="w-full border-gray-300 rounded-md" placeholder="Tell us what's going on…">{{ old('body') }}</textarea>
                </div>
                <button class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-md font-medium hover:bg-emerald-700">
                    <i class="fa-solid fa-paper-plane"></i> Submit ticket
                </button>
            </form>
        </div>
    </div>
</x-client-layout>
