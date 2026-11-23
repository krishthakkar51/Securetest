<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="auth-check" content="0">

        <title>{{ config('app.name', 'SecureTest') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 bg-[#164e3b] min-h-screen flex flex-col justify-center items-center p-4 sm:p-6 selection:bg-[#a3cbbe] selection:text-[#164e3b]">
        <div class="w-full max-w-md my-auto">
            {{ $slot }}
        </div>
    </body>
</html>
