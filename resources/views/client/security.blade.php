<x-client-layout title="Security">
    <div class="max-w-2xl space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Security &amp; app lock</h2>
            <p class="text-sm text-gray-500">Protect your account with a PIN and biometric unlock.</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
        @endif

        {{-- PIN --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 grid place-items-center"><i class="fa-solid fa-shield-halved"></i></div>
                <div>
                    <h3 class="font-semibold text-gray-900">App-lock PIN</h3>
                    <p class="text-xs text-gray-500">{{ $hasPin ? 'A PIN is currently set. The app locks after 30 minutes of inactivity.' : 'No PIN set yet.' }}</p>
                </div>
                @if ($hasPin)<span class="ml-auto text-xs px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800">Enabled</span>@endif
            </div>

            <form method="POST" action="{{ route('security.pin.set') }}" class="space-y-4 text-sm">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-gray-700 mb-1">{{ $hasPin ? 'New PIN' : 'PIN' }} (4–6 digits)</label>
                        <input type="password" name="pin" inputmode="numeric" pattern="[0-9]*" maxlength="6" required
                               class="w-full border-gray-300 rounded-md tracking-widest">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-1">Confirm PIN</label>
                        <input type="password" name="pin_confirmation" inputmode="numeric" pattern="[0-9]*" maxlength="6" required
                               class="w-full border-gray-300 rounded-md tracking-widest">
                    </div>
                </div>
                <button class="px-4 py-2 bg-emerald-600 text-white rounded-md">{{ $hasPin ? 'Update PIN' : 'Set PIN' }}</button>
            </form>

            @if ($hasPin)
                <form method="POST" action="{{ route('lock.now') }}" class="mt-3 inline">@csrf
                    <button class="px-4 py-2 border rounded-md text-gray-600 text-sm"><i class="fa-solid fa-lock mr-1"></i> Lock now</button>
                </form>
            @endif

            @if ($hasPin)
                <form method="POST" action="{{ route('security.pin.remove') }}" class="mt-4" onsubmit="return confirm('Remove the app-lock PIN? Biometric unlock will also stop working.')">
                    @csrf @method('DELETE')
                    <button class="text-sm text-red-600 hover:underline">Remove PIN</button>
                </form>
            @endif
        </div>

        {{-- Biometric --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 grid place-items-center"><i class="fa-solid fa-fingerprint"></i></div>
                <div>
                    <h3 class="font-semibold text-gray-900">Biometric unlock (Face ID / fingerprint)</h3>
                    <p class="text-xs text-gray-500">Use your device biometrics to unlock the app quickly.</p>
                </div>
            </div>
            <div id="biometric-setup" class="text-sm text-gray-500">
                <p><i class="fa-solid fa-circle-info text-gray-400"></i> Set a PIN first, then enable biometrics. Biometric setup will appear here once available on your device.</p>
            </div>
        </div>
    </div>

</x-client-layout>
