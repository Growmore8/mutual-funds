<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'GrowthCapital Funds') }}</title>
        <script>(function(){try{var t=localStorage.getItem('theme');if(t==='dark'||(!t&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark');}}catch(e){}})();</script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans text-gray-900 antialiased">
        <div class="min-h-full flex flex-col justify-center items-center px-6 py-10 bg-gray-100">
            <a href="/" class="mb-6 text-center">
                <div class="text-2xl font-bold text-[#0a1730]">Growth<span class="text-emerald-500">Capital</span></div>
                <p class="text-xs text-gray-400">Mutual Funds</p>
            </a>

            <div class="w-full sm:max-w-md px-6 py-6 bg-white shadow-xl rounded-2xl">
                {{ $slot }}
            </div>

            <p class="text-xs text-gray-400 mt-6">&copy; {{ date('Y') }} GrowthCapital Ltd · License 11064258</p>
        </div>
    </body>
</html>
