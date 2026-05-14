<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} - {{ $header ?? 'Admin' }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <livewire:layout.navigation />

            <div class="flex">
                <aside class="w-64 min-h-screen bg-white border-r border-gray-200 shrink-0">
                    <div class="p-4">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Management</div>
                        <nav class="space-y-1">
                            <a href="/admin" @class([
                                'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                'bg-indigo-50 text-indigo-700' => request()->is('admin') && !request()->is('admin/*'),
                                'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => !(request()->is('admin') && !request()->is('admin/*')),
                            ])>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                Dashboard
                            </a>
                            <a href="/admin/licenses" @class([
                                'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                'bg-indigo-50 text-indigo-700' => request()->is('admin/licenses*'),
                                'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => !request()->is('admin/licenses*'),
                            ])>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                Licenses
                            </a>
                        </nav>
                    </div>

                    <div class="p-4 pt-0">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Master Data</div>
                        <nav class="space-y-1">
                            <a href="/admin/products" @class([
                                'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                'bg-indigo-50 text-indigo-700' => request()->is('admin/products*'),
                                'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => !request()->is('admin/products*'),
                            ])>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Products
                            </a>
                            <a href="/admin/plans" @class([
                                'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                'bg-indigo-50 text-indigo-700' => request()->is('admin/plans*'),
                                'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => !request()->is('admin/plans*'),
                            ])>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                </svg>
                                Subscription Plans
                            </a>
                        </nav>
                    </div>

                    <div class="p-4 pt-0">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Operations</div>
                        <nav class="space-y-1">
                            <a href="/admin/activation-requests" @class([
                                'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                'bg-indigo-50 text-indigo-700' => request()->is('admin/activation-requests*'),
                                'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => !request()->is('admin/activation-requests*'),
                            ])>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                </svg>
                                Activation Requests
                            </a>
                            <a href="/admin/devices" @class([
                                'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                'bg-indigo-50 text-indigo-700' => request()->is('admin/devices*'),
                                'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => !request()->is('admin/devices*'),
                            ])>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                Devices
                            </a>
                        </nav>
                    </div>

                    <div class="p-4 pt-0">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">System</div>
                        <nav class="space-y-1">
                            <a href="/admin/audit-logs" @class([
                                'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                'bg-indigo-50 text-indigo-700' => request()->is('admin/audit-logs*'),
                                'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => !request()->is('admin/audit-logs*'),
                            ])>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Audit Logs
                            </a>
                        </nav>
                    </div>
                </aside>

                <main class="flex-1 min-w-0">
                    @if (session('success'))
                        <div class="mx-6 mt-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mx-6 mt-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (isset($header))
                        <div class="px-6 pt-6 pb-2">
                            <h2 class="text-xl font-semibold text-gray-800">{{ $header }}</h2>
                        </div>
                    @endif

                    <div class="p-6">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
