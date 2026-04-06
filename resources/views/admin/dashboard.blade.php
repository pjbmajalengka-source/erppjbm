@extends('layouts.admin')

@section('title', 'Dashboard Overview')
@section('page_title', 'Dashboard Overview')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <!-- Stat Card: Total Employees -->
    <div class="glass p-6 rounded-2xl shadow-sm hover-scale group border border-white/10">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase font-bold opacity-40 tracking-widest text-pjb-secondary dark:text-white">Total Employees</p>
                <h3 class="text-3xl font-bold mt-1">0</h3>
            </div>
            <div class="w-12 h-12 bg-pjb-primary/10 rounded-xl flex items-center justify-center text-pjb-primary group-hover:bg-pjb-primary transition-all group-hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
        </div>
        <div class="mt-4 flex items-center text-xs text-green-500 font-medium">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
            0% from last month
        </div>
    </div>

    <!-- Stat Card: Wallet Exposure (Debt) -->
    <div class="glass p-6 rounded-2xl shadow-sm hover-scale group border border-white/10">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase font-bold opacity-40 tracking-widest text-pjb-secondary dark:text-white">Wallet Liabilities</p>
                <h3 class="text-3xl font-bold mt-1 text-red-500">Rp 0</h3>
            </div>
            <div class="w-12 h-12 bg-red-500/10 rounded-xl flex items-center justify-center text-red-500 group-hover:bg-red-500 transition-all group-hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>
        <div class="mt-4 text-xs text-gray-500">Net employee debt to store</div>
    </div>

    <!-- Stat Card: Attendance Rate -->
    <div class="glass p-6 rounded-2xl shadow-sm hover-scale group border border-white/10">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase font-bold opacity-40 tracking-widest text-pjb-secondary dark:text-white">Active Shift</p>
                <h3 class="text-3xl font-bold mt-1 text-pjb-accent">0%</h3>
            </div>
            <div class="w-12 h-12 bg-pjb-accent/10 rounded-xl flex items-center justify-center text-pjb-accent group-hover:bg-pjb-accent transition-all group-hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>
        <div class="mt-4 text-xs text-gray-400">Target: 100% attendance</div>
    </div>

    <!-- Stat Card: Total Payroll (Est) -->
    <div class="glass p-6 rounded-2xl shadow-sm hover-scale group border border-white/10">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase font-bold opacity-40 tracking-widest text-pjb-secondary dark:text-white">Est. Payout</p>
                <h3 class="text-3xl font-bold mt-1">Rp 0</h3>
            </div>
            <div class="w-12 h-12 bg-pjb-secondary/10 rounded-xl flex items-center justify-center text-pjb-secondary dark:text-white group-hover:bg-pjb-secondary transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
            </div>
        </div>
        <div class="mt-4 text-xs text-gray-500">Live estimate for current cycle</div>
    </div>
</div>

<!-- Recent Activity & Charts -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-12 pb-12">
    <div class="lg:col-span-2 glass rounded-2xl p-8 border border-white/10">
        <h3 class="text-xl font-bold tracking-tight mb-8">Attendance Overview</h3>
        <div class="h-64 flex items-center justify-center text-gray-400 border-2 border-dashed border-gray-100 dark:border-white/5 rounded-2xl">
            Charts will be rendered here (Livewire/ApexCharts)
        </div>
    </div>
    
    <div class="glass rounded-2xl p-8 border border-white/10">
        <h3 class="text-xl font-bold tracking-tight mb-8">Recent Payouts</h3>
        <div class="space-y-6">
            <div class="flex items-center gap-4 py-2 border-b border-gray-50 dark:border-white/5">
                <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center text-green-500 font-bold">A</div>
                <div class="flex-1">
                    <p class="text-sm font-bold">Ahmad Fatih</p>
                    <p class="text-xs text-gray-400 font-medium tracking-tight">SALARY LEDGER</p>
                </div>
                <p class="text-sm font-bold text-pjb-secondary">Rp 2.450k</p>
            </div>
            
            <div class="text-center py-4">
                <a href="#" class="text-sm font-bold text-pjb-primary hover:underline">View All Transactions &rarr;</a>
            </div>
        </div>
    </div>
</div>
@endsection
