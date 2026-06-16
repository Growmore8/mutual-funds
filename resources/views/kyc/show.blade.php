<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verify your identity · GrowthCapital Funds</title>
    <meta name="theme-color" content="#0a1730">
    <link rel="icon" href="/logo.png" type="image/png">
    <script>(function(){try{var t=localStorage.getItem('theme');if(t==='dark'||(!t&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark');}}catch(e){}})();</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-100 text-gray-800">
<div class="min-h-full flex flex-col">
    {{-- Minimal top bar (no app navigation until KYC is approved) --}}
    <header class="bg-white border-b">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <img src="/logo.png" alt="" class="w-8 h-8" onerror="this.style.display='none'">
                <div class="font-bold text-[#0a1730]">Growth<span class="text-emerald-500">Capital</span></div>
            </div>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="text-sm text-gray-500 hover:text-gray-800"><i class="fa-solid fa-right-from-bracket mr-1"></i> Log out</button>
            </form>
        </div>
    </header>

    <main class="flex-1 flex items-start justify-center px-4 py-10">
        <div class="w-full max-w-lg">
            <div class="text-center mb-6">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-50 text-emerald-600 grid place-items-center text-2xl mb-3"><i class="fa-solid fa-id-card"></i></div>
                <h1 class="text-xl font-bold text-gray-900">Identity verification</h1>
                <p class="text-sm text-gray-500">Verify your identity to unlock your account.</p>
            </div>

            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
            @endif

            @if ($user->kyc_status === 'submitted')
                {{-- Already uploaded → pending review --}}
                <div class="bg-white shadow-sm rounded-2xl p-8 text-center">
                    <div class="w-12 h-12 mx-auto rounded-full bg-amber-100 text-amber-700 grid place-items-center text-xl mb-3"><i class="fa-solid fa-hourglass-half"></i></div>
                    <h2 class="font-semibold text-gray-900">Under review</h2>
                    <p class="text-sm text-gray-500 mt-1">Your documents have been submitted. We'll notify you once your identity is approved — usually within 24 hours.</p>
                </div>
            @else
                {{-- not_submitted or rejected → upload module --}}
                <div class="bg-white shadow-sm rounded-2xl p-6">
                    @if ($user->kyc_status === 'rejected')
                        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">Your previous submission was not approved. Please re-upload clear photos.</div>
                    @endif
                    <h2 class="font-semibold text-gray-900 mb-1">National ID / Passport</h2>
                    <p class="text-sm text-gray-500 mb-4">Upload a clear photo of the <strong>front</strong> and <strong>back</strong>.</p>
                    <form method="POST" action="{{ route('kyc.store') }}" enctype="multipart/form-data" class="space-y-4 text-sm">
                        @csrf
                        <div>
                            <label class="block text-gray-700 mb-1">Document number (optional)</label>
                            <input type="text" name="document_number" class="w-full border-gray-300 rounded-md" placeholder="National ID / Passport number">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-1">Front side</label>
                                <input name="front" type="file" accept=".jpg,.jpeg,.png,.pdf" required class="w-full text-xs file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-gray-100 file:text-gray-700">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-1">Back side</label>
                                <input name="back" type="file" accept=".jpg,.jpeg,.png,.pdf" required class="w-full text-xs file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-gray-100 file:text-gray-700">
                            </div>
                        </div>
                        <p class="text-xs text-gray-400">JPG, PNG or PDF · max 5 MB each.</p>
                        <button class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 rounded-lg"><i class="fa-solid fa-paper-plane mr-1"></i> Submit for review</button>
                    </form>
                </div>
            @endif
        </div>
    </main>
</div>
</body>
</html>
