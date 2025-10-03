<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen theme-body">
    <div class="flex min-h-screen items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <!-- Logo / Brand -->
            <div class="mb-8 text-center">
                <a href="/" class="inline-flex items-center gap-2 text-3xl font-bold theme-title">
                    <i class="ti ti-brand-laravel text-red-500"></i>
                    <span>{{ config('app.name', 'Laravel') }}</span>
                </a>
                <p class="mt-2 text-sm theme-body">
                    @yield('subtitle', 'Bienvenue sur notre plateforme')
                </p>
            </div>

            <!-- Auth Card -->
            <div class="overflow-hidden rounded-2xl theme-surface shadow-xl">
                <div class="p-8">
                    <!-- Title -->
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold theme-title">
                            @yield('header')
                        </h2>
                        @hasSection('description')
                            <p class="mt-2 text-sm theme-body">
                                @yield('description')
                            </p>
                        @endif
                    </div>

                    <!-- Success Message -->
                    @if (session('success'))
                        <div class="mb-6 flex items-center gap-3 rounded-lg theme-surface p-4">
                            <i class="ti ti-circle-check text-xl text-green-600"></i>
                            <span class="text-sm theme-body">{{ session('success') }}</span>
                        </div>
                    @endif

                    <!-- Error Message -->
                    @if (session('error'))
                        <div class="mb-6 flex items-center gap-3 rounded-lg theme-danger p-4">
                            <i class="ti ti-alert-circle text-xl text-red-700"></i>
                            <span class="text-sm text-red-800">{{ session('error') }}</span>
                        </div>
                    @endif

                    <!-- Validation Errors -->
                    @if ($errors->any())
                        <div class="mb-6 rounded-lg theme-danger p-4">
                            <div class="flex items-center gap-2 text-red-800">
                                <i class="ti ti-alert-triangle text-xl"></i>
                                <span class="font-semibold">Erreur de validation</span>
                            </div>
                            <ul class="mt-3 space-y-1 text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li class="flex items-start gap-2">
                                        <i class="ti ti-point-filled mt-1 text-xs"></i>
                                        <span>{{ $error }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Main Content -->
                    @yield('content')
                </div>

                <!-- Footer Links -->
                @hasSection('footer')
                    <div class="border-t theme-divider theme-muted px-8 py-4">
                        @yield('footer')
                    </div>
                @endif
            </div>

            <!-- Additional Links -->
            @hasSection('additional_links')
                <div class="mt-6 text-center text-sm theme-body">
                    @yield('additional_links')
                </div>
            @endif
        </div>
    </div>
</body>

</html>
