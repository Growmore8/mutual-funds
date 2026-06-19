<x-client-layout title="Security">
    <div class="max-w-2xl space-y-6">
        <x-back-link />
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Security &amp; app lock</h2>
            <p class="text-sm text-gray-500">Protect your account with a PIN and biometric unlock.</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
        @endif

        {{-- Push notifications --}}
        <div class="bg-white dark:bg-white/[0.04] dark:border dark:border-white/[0.06] rounded-2xl shadow-sm p-6" x-data="{ status: (window.Notification && Notification.permission) || 'default' }">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300 grid place-items-center"><i class="fa-solid fa-bell"></i></div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Push notifications</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Get alerts for deposits, profit, withdrawals &amp; referrals — even when the app is closed.</p>
                </div>
                <span x-show="status==='granted'" class="ml-auto text-xs px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300">Enabled</span>
            </div>
            <button type="button" x-show="status!=='granted'" @click="status = await window.enablePush()"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-sm font-medium">
                <i class="fa-solid fa-bell mr-1"></i> Enable notifications
            </button>
            <p x-show="status==='denied'" x-cloak class="text-xs text-amber-600 mt-2">Notifications are blocked in your browser/phone settings — allow them there to enable.</p>
            <p class="text-[11px] text-gray-400 mt-2">On iPhone, add the app to your Home Screen first, then enable.</p>
        </div>

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
                <form method="POST" action="{{ route('security.pin.remove') }}" class="mt-4" onsubmit="return confirm('Remove the app-lock PIN? Biometric unlock will also stop working.')">
                    @csrf @method('DELETE')
                    <button class="text-sm text-red-600 hover:underline">Remove PIN</button>
                </form>
            @endif
        </div>

        {{-- Biometric --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 grid place-items-center"><i class="fa-solid fa-fingerprint"></i></div>
                <div>
                    <h3 class="font-semibold text-gray-900">Biometric unlock (Face ID / fingerprint)</h3>
                    <p class="text-xs text-gray-500">Use your device biometrics to unlock the app quickly.</p>
                </div>
                @if ($hasPasskey)<span class="ml-auto text-xs px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800">Enabled</span>@endif
            </div>

            @if (! $hasPin)
                <p class="text-sm text-gray-500"><i class="fa-solid fa-circle-info text-gray-400"></i> Set an app-lock PIN above first — biometrics unlock the same lock.</p>
            @else
                <div id="bio-unsupported" class="hidden text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3">
                    This device or browser doesn't support biometric passkeys. Use your PIN to unlock.
                </div>
                <div id="bio-msg" class="hidden text-sm rounded-lg p-3 mb-3"></div>

                @if ($hasPasskey)
                    <p class="text-sm text-gray-600 mb-3">Biometric unlock is active. You can add this device too, or turn it off.</p>
                    <div class="flex gap-2">
                        <button type="button" id="bio-register" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm"><i class="fa-solid fa-plus mr-1"></i> Add this device</button>
                        <form method="POST" action="{{ route('webauthn.destroy') }}" onsubmit="return confirm('Turn off biometric unlock on all devices?')">
                            @csrf @method('DELETE')
                            <button class="px-4 py-2 border rounded-md text-red-600 text-sm">Turn off biometrics</button>
                        </form>
                    </div>
                @else
                    <button type="button" id="bio-register" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm">
                        <i class="fa-solid fa-fingerprint mr-1"></i> Enable Face ID / fingerprint
                    </button>
                @endif
            @endif
        </div>
    </div>

    @if ($hasPin)
        <script src="https://cdn.jsdelivr.net/npm/@laragear/webpass@2/dist/webpass.js"></script>
        <script>
            (function () {
                var btn = document.getElementById('bio-register');
                var msg = document.getElementById('bio-msg');
                var unsupported = document.getElementById('bio-unsupported');
                if (typeof Webpass === 'undefined') return;
                if (Webpass.isUnsupported && Webpass.isUnsupported()) {
                    if (unsupported) unsupported.classList.remove('hidden');
                    if (btn) btn.disabled = true;
                    return;
                }
                function show(text, ok) {
                    if (!msg) return;
                    msg.textContent = text;
                    msg.className = 'text-sm rounded-lg p-3 mb-3 ' + (ok ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-red-50 border border-red-200 text-red-700');
                    msg.classList.remove('hidden');
                }
                if (btn) btn.addEventListener('click', async function () {
                    btn.disabled = true;
                    try {
                        const { success } = await Webpass.attest('{{ route('webauthn.register.options') }}', '{{ route('webauthn.register') }}');
                        if (success) { show('Biometric unlock enabled on this device.', true); setTimeout(function(){ location.reload(); }, 1200); }
                        else { show('Could not enable biometrics. Please try again.', false); btn.disabled = false; }
                    } catch (e) {
                        show('Biometric setup was cancelled or failed.', false);
                        btn.disabled = false;
                    }
                });
            })();
        </script>
    @endif
</x-client-layout>
