<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ config('app.name') }} — Sistem Antrian Pasien">
    <meta name="theme-color" content="#3b82f6">

    <title>{{ $title ?? config('app.name', 'Sistem Antrian Pasien') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles & Scripts (Vite + TailwindCSS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body class="bg-gray-50 text-gray-900 antialiased min-h-screen flex flex-col">

    <!-- Top Navigation Bar -->
    <nav class="bg-blue-500 shadow-md">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">

                <!-- Logo / App Name -->
                <div class="flex items-center gap-3">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 text-white no-underline">
                        <!-- Simple cross/medical icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="text-lg font-semibold tracking-tight">
                            {{ config('app.name', 'Sistem Antrian Pasien') }}
                        </span>
                    </a>
                </div>

                <!-- Desktop Nav Links -->
                <div class="hidden md:flex items-center gap-4">
                    <a href="{{ route('home') }}"
                       class="rounded px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-600 transition-colors">
                        Beranda
                    </a>
                    @auth
                        <a href="{{ url('/admin/polyclinics') }}"
                           class="rounded px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-600 transition-colors">
                            Admin
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="rounded px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-600 transition-colors">
                                Keluar
                            </button>
                        </form>
                    @endauth
                </div>

                <!-- Mobile Hamburger (pure CSS toggle via checkbox hack — no JS required) -->
                <div class="md:hidden">
                    <label for="mobile-menu-toggle" class="cursor-pointer text-white p-2" aria-label="Buka menu">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>
                </div>

            </div>
        </div>

        <!-- Mobile Menu (hidden by default, shown with checkbox toggle) -->
        <input type="checkbox" id="mobile-menu-toggle" class="peer hidden" aria-hidden="true">
        <div class="hidden peer-checked:block bg-blue-600 md:hidden">
            <div class="space-y-1 px-4 pb-3 pt-2">
                <a href="{{ route('home') }}"
                   class="block rounded px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                    Beranda
                </a>
                @auth
                    <a href="{{ url('/admin/polyclinics') }}"
                       class="block rounded px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                        Admin
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full text-left block rounded px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                            Keluar
                        </button>
                    </form>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 mt-4">
            <div class="rounded-md bg-green-50 border border-green-200 p-4 text-green-800 text-sm">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 mt-4">
            <div class="rounded-md bg-red-50 border border-red-200 p-4 text-red-800 text-sm">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Page Content -->
    <main class="flex-1 mx-auto max-w-7xl w-full px-4 py-6 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-200 bg-white py-4 text-center text-xs text-gray-500">
        &copy; {{ date('Y') }} {{ config('app.name', 'Sistem Antrian Pasien') }}. Hak cipta dilindungi.
    </footer>

    <!-- Livewire Scripts -->
    @livewireScripts
</body>
</html>
