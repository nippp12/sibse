<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js']) {{-- jika pakai Vite --}}
    @livewireStyles
</head>
<body class="min-h-screen bg-white dark:bg-gray-900 flex flex-col">

    {{-- Navbar --}}
    @include('components.navbar')

    {{-- Konten utama --}}
    <main class="flex-grow">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
