<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('We have emailed a 6-digit verification code to') }} <strong>{{ $email }}</strong>.
        {{ __('Enter it below to verify your email. The code expires in 10 minutes.') }}
    </div>

    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('otp.verify') }}">
        @csrf
        <div>
            <x-input-label for="code" :value="__('Verification code')" />
            <x-text-input id="code" class="block mt-1 w-full tracking-[0.5em] text-center text-lg"
                          type="text" name="code" inputmode="numeric" maxlength="6"
                          required autofocus autocomplete="one-time-code" placeholder="------" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <button form="resend-form" class="underline text-sm text-gray-600 hover:text-gray-900">
                {{ __('Resend code') }}
            </button>
            <x-primary-button>{{ __('Verify email') }}</x-primary-button>
        </div>
    </form>

    <form id="resend-form" method="POST" action="{{ route('otp.resend') }}" class="hidden">@csrf</form>
</x-guest-layout>
