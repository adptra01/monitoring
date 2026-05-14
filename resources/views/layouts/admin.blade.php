<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} - Admin</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <livewire:layout.navigation />

            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex items-center justify-between">
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            {{ $header }}
                        </h2>
                    </div>
                </header>
            @endif

            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-6">
                @if (session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="flex gap-6">
                    <nav class="w-56 shrink-0">
                        <div class="bg-white rounded-lg shadow-sm p-4 space-y-1">
                            <a href="/admin" @class(['block px-3 py-2 rounded-md text-sm font-medium', 'bg-gray-100 text-gray-900' => request()->is('admin'), 'text-gray-600 hover:bg-gray-50' => !request()->is('admin')])>
                                Dashboard
                            </a>
                            <a href="/admin/licenses" @class(['block px-3 py-2 rounded-md text-sm font-medium', 'bg-gray-100 text-gray-900' => request()->is('admin/licenses*'), 'text-gray-600 hover:bg-gray-50' => !request()->is('admin/licenses*')])>
                                Licenses
                            </a>
                            <a href="/admin/products" @class(['block px-3 py-2 rounded-md text-sm font-medium', 'bg-gray-100 text-gray-900' => request()->is('admin/products*'), 'text-gray-600 hover:bg-gray-50' => !request()->is('admin/products*')])>
                                Products
                            </a>
                            <a href="/admin/plans" @class(['block px-3 py-2 rounded-md text-sm font-medium', 'bg-gray-100 text-gray-900' => request()->is('admin/plans*'), 'text-gray-600 hover:bg-gray-50' => !request()->is('admin/plans*')])>
                                Plans
                            </a>
                            <a href="/admin/activation-requests" @class(['block px-3 py-2 rounded-md text-sm font-medium', 'bg-gray-100 text-gray-900' => request()->is('admin/activation-requests*'), 'text-gray-600 hover:bg-gray-50' => !request()->is('admin/activation-requests*')])>
                                Activation Requests
                            </a>
                            <a href="/admin/devices" @class(['block px-3 py-2 rounded-md text-sm font-medium', 'bg-gray-100 text-gray-900' => request()->is('admin/devices*'), 'text-gray-600 hover:bg-gray-50' => !request()->is('admin/devices*')])>
                                Devices
                            </a>
                            <a href="/admin/audit-logs" @class(['block px-3 py-2 rounded-md text-sm font-medium', 'bg-gray-100 text-gray-900' => request()->is('admin/audit-logs*'), 'text-gray-600 hover:bg-gray-50' => !request()->is('admin/audit-logs*')])>
                                Audit Logs
                            </a>
                        </div>
                    </nav>

                    <main class="flex-1 min-w-0">
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            {{ $slot }}
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
