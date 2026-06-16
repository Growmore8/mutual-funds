<x-admin-layout title="Security">
    <div class="max-w-2xl space-y-6">
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
        @endif

        <div class="bg-white shadow rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Change password</h2>
            <p class="text-sm text-gray-500 mb-5">Set a new admin password.</p>
            <form method="POST" action="{{ route('admin.settings.password') }}" class="space-y-4 text-sm">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><label class="block text-gray-700 mb-1">New password</label><input type="password" name="password" required class="w-full border-gray-300 rounded-md"></div>
                    <div><label class="block text-gray-700 mb-1">Confirm new password</label><input type="password" name="password_confirmation" required class="w-full border-gray-300 rounded-md"></div>
                </div>
                <button class="px-4 py-2 bg-gray-800 text-white rounded-md">Change password</button>
            </form>
        </div>
    </div>
</x-admin-layout>
