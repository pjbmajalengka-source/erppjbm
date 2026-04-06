@extends('layouts.app')

@section('title', 'Welcome to ERP Puncak JB')

@section('navigation')
    @if (Route::has('login'))
        @auth
            <a href="{{ url('/dashboard') }}" class="px-4 py-2 text-sm font-medium hover:text-pjb-primary transition-colors">Dashboard</a>
        @else
            <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium hover:text-pjb-primary transition-colors">Log in</a>
            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="ml-2 px-4 py-2 bg-pjb-primary text-white rounded-lg text-sm font-medium shadow-sm hover:opacity-90 transition-all">Register</a>
            @endif
        @endauth
    @endif
@endsection

@section('content')
<div class="space-y-12 py-8">
    <!-- Hero Section -->
    <div class="text-center space-y-4">
        <h1 class="text-4xl md:text-5xl font-bold tracking-tight text-pjb-secondary dark:text-white">
            Modern ERP for <span class="text-pjb-primary">Puncak JB</span>
        </h1>
        <p class="max-w-2xl mx-auto text-lg text-gray-600 dark:text-gray-400">
            A high-performance business management system built with Laravel 13 and tailored for excellence.
        </p>
    </div>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Get Started Card -->
        <div class="glass p-8 rounded-2xl shadow-sm hover-scale group">
            <div class="w-12 h-12 bg-pjb-primary/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-pjb-primary/20 transition-colors">
                <svg class="w-6 h-6 text-pjb-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <h2 class="text-xl font-bold mb-2 dark:text-white">Let's get started</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Explore the features and capabilities of the ERP-PJBM ecosystem.</p>
            <div class="flex gap-3">
                <a href="{{ route('login') }}" class="touch-target px-6 bg-pjb-primary text-white rounded-xl font-semibold shadow-sm overflow-hidden relative group/btn">
                    <span class="relative z-10">Login</span>
                </a>
            </div>
        </div>

        <!-- Documentation Card -->
        <div class="glass p-8 rounded-2xl shadow-sm hover-scale group">
            <div class="w-12 h-12 bg-pjb-secondary/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-pjb-secondary/20 transition-colors">
                <svg class="w-6 h-6 text-pjb-secondary dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <h2 class="text-xl font-bold mb-2 dark:text-white">Documentation</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Read the offical Laravel docs to master the architecture.</p>
            <a href="https://laravel.com/docs" class="text-pjb-primary font-semibold hover:underline">Read Docs &rarr;</a>
        </div>

        <!-- Video Tutorials -->
        <div class="glass p-8 rounded-2xl shadow-sm hover-scale group">
            <div class="w-12 h-12 bg-pjb-accent/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-pjb-accent/20 transition-colors">
                <svg class="w-6 h-6 text-pjb-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
            </div>
            <h2 class="text-xl font-bold mb-2 dark:text-white">Laracasts</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Level up with video tutorials covering everything from PHP to JS.</p>
            <a href="https://laracasts.com" class="text-pjb-accent font-semibold hover:underline">Watch Tutorials &rarr;</a>
        </div>
    </div>

    <!-- Footer Stats -->
    <div class="text-center text-sm text-gray-500 dark:text-gray-500 border-t border-gray-200 dark:border-white/10 pt-8">
        Laravel v{{ app()->version() }} (PHP v{{ PHP_VERSION }})
    </div>
</div>
@endsection

@section('bottom-nav')
    <div class="flex flex-col items-center gap-1 group">
        <svg class="w-6 h-6 text-pjb-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <span class="text-[10px] uppercase font-bold text-pjb-primary">Home</span>
    </div>
    <div class="flex flex-col items-center gap-1 opacity-50">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
        <span class="text-[10px] uppercase font-bold">Login</span>
    </div>
@endsection
