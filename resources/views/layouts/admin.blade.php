<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - ERP Puncak JB</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-stone-50 dark:bg-zinc-950 text-pjb-secondary dark:text-zinc-300">
    <div class="flex min-h-screen">
        <!-- Sidebar (Glassmorphism) -->
        <aside class="hidden lg:flex w-72 flex-col fixed inset-y-0 z-50 glass border-r border-white/20 shadow-2xl">
            <div class="p-8">
                <h1 class="text-2xl font-bold tracking-tighter text-pjb-primary">ERP-PJBM</h1>
                <p class="text-[10px] uppercase font-bold opacity-50 tracking-widest mt-1">Admin Control Center</p>
            </div>
            
            <nav class="flex-1 px-4 py-4 space-y-2">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-pjb-primary/10 text-pjb-primary font-semibold transition-all">
                    <svg class="w-5 h-5 text-pjb-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                    Dashboard
                </a>
                
                <div class="pt-6 pb-2 px-4 text-[10px] uppercase font-bold opacity-30 tracking-widest">Management</div>
                
                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/40 dark:hover:bg-white/5 transition-all text-gray-500 hover:text-pjb-secondary dark:hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    Employees
                </a>
                
                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/40 dark:hover:bg-white/5 transition-all text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    Wallet
                </a>
                
                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/40 dark:hover:bg-white/5 transition-all text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                    Payroll
                </a>
            </nav>

            <div class="p-6 border-t border-white/20 text-xs text-gray-400">
                PJB Ecosystem &copy; 2026
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 lg:ml-72 bg-pjb-bg-light dark:bg-pjb-bg-dark min-h-screen transition-colors duration-300">
            <!-- Top Nav -->
            <header class="h-16 glass sticky top-0 z-40 px-8 flex items-center justify-between border-b border-white/20">
                <div class="flex items-center gap-4">
                    <button class="lg:hidden p-2 rounded-lg hover:bg-white/10">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <h2 class="text-lg font-semibold tracking-tight">@yield('page_title')</h2>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-pjb-primary/20 flex items-center justify-center text-pjb-primary font-bold">
                            {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
                        </div>
                        <span class="hidden md:block text-sm font-medium">{{ Auth::user()->name ?? 'Administrator' }}</span>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-8">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
