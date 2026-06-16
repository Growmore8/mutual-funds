<x-admin-layout title="Profile">
    <div class="max-w-2xl space-y-6">
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
        @endif

        {{-- Profile / email --}}
        <div class="bg-white shadow rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Admin profile</h2>
            <p class="text-sm text-gray-500 mb-5">Your name and login email.</p>
            <form method="POST" action="{{ route('admin.settings.profile') }}" class="space-y-4 text-sm">
                @csrf @method('PATCH')
                <div><label class="block text-gray-700 mb-1">Name</label><input name="name" value="{{ old('name',$admin->name) }}" required class="w-full border-gray-300 rounded-md"></div>
                <div><label class="block text-gray-700 mb-1">Email (login)</label><input type="email" name="email" value="{{ old('email',$admin->email) }}" required class="w-full border-gray-300 rounded-md"></div>
                <button class="px-4 py-2 bg-emerald-600 text-white rounded-md">Save profile</button>
            </form>
        </div>
    </div>
</x-admin-layout>
