<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ __('Profile Information') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('These details were set when you registered and are locked. Contact support if anything needs to change.') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <dl class="mt-6 space-y-4 text-sm">
        <div>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('Name') }}</dt>
            <dd class="mt-1 flex items-center justify-between rounded-lg bg-gray-50 dark:bg-white/5 px-3 py-2.5 text-gray-900 dark:text-gray-100">
                <span>{{ $user->name }}</span><i class="fa-solid fa-lock text-gray-300 dark:text-gray-500 text-xs"></i>
            </dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('Email') }}</dt>
            <dd class="mt-1 flex items-center justify-between rounded-lg bg-gray-50 dark:bg-white/5 px-3 py-2.5 text-gray-900 dark:text-gray-100">
                <span>{{ $user->email }}</span><i class="fa-solid fa-lock text-gray-300 dark:text-gray-500 text-xs"></i>
            </dd>
            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="text-sm mt-2 text-gray-800 dark:text-gray-300">
                    {{ __('Your email address is unverified.') }}
                    <button form="send-verification" class="underline text-sm text-emerald-600 hover:text-emerald-700">{{ __('Re-send verification email.') }}</button>
                </p>
                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 font-medium text-sm text-green-600">{{ __('A new verification link has been sent to your email address.') }}</p>
                @endif
            @endif
        </div>
        @if ($user->phone)
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('Phone') }}</dt>
                <dd class="mt-1 flex items-center justify-between rounded-lg bg-gray-50 dark:bg-white/5 px-3 py-2.5 text-gray-900 dark:text-gray-100">
                    <span>{{ $user->phone }}</span><i class="fa-solid fa-lock text-gray-300 dark:text-gray-500 text-xs"></i>
                </dd>
            </div>
        @endif
        @if ($user->country)
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('Country') }}</dt>
                <dd class="mt-1 flex items-center justify-between rounded-lg bg-gray-50 dark:bg-white/5 px-3 py-2.5 text-gray-900 dark:text-gray-100">
                    <span>{{ $user->country }}</span><i class="fa-solid fa-lock text-gray-300 dark:text-gray-500 text-xs"></i>
                </dd>
            </div>
        @endif
    </dl>
</section>
