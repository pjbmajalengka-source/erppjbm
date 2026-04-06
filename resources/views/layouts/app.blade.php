<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'ERP-PJBM'))</title>

    <!-- Fonts: Inter Tight for a premium, high-density ERP feel -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full font-sans antialiased bg-pjb-bg-light dark:bg-pjb-bg-dark text-pjb-secondary dark:text-gray-300">
    <div id="app" class="min-h-screen flex flex-col">
        <!-- Main Header / Navigation -->
        <header class="sticky top-0 z-50 glass shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <!-- Logo & Brand -->
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-pjb-primary flex items-center justify-center shadow-md">
                            <span class="text-white font-bold">P</span>
                        </div>
                        <span class="hidden sm:inline-block font-bold text-lg tracking-tight dark:text-white">ERP Puncak JB</span>
                        <span class="sm:hidden font-bold dark:text-white">ERP-PJBM</span>
                    </div>

                    <!-- Desktop Navigation (Simplified for now) -->
                    <nav class="hidden md:flex items-center gap-1">
                        @yield('navigation')
                    </nav>

                    <!-- Mobile Menu Button -->
                    <div class="flex md:hidden">
                        <button type="button" class="touch-target rounded-md text-pjb-secondary dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none" aria-label="Toggle Menu">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>

        <!-- Footer / Bottom Navigation (Mobile Only) -->
        <footer class="md:hidden sticky bottom-0 z-50 glass border-t border-white/10 py-2 px-6 flex justify-around items-center">
            @yield('bottom-nav')
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
