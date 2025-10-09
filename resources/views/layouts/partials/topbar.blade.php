<!-- Top Header -->
<div class="w-full relative ">

    <header
        class="flex h-16 items-center justify-between border-b theme-divider  px-6 backdrop-blur-md absolute top-0 left-0 right-0 z-30">
        <!-- Mobile Menu Button -->
        <button onclick="window.__toggleSidebar()" class="theme-muted-text hover-theme-muted rounded-lg p-2 lg:hidden">
            <i class="ti ti-menu-2 text-2xl"></i>
        </button>

        <!-- Page Title -->
        <h1 class="text-xl font-semibold theme-title ">
            @yield('page-title', 'Tableau de bord')
        </h1>

        <!-- Right Actions -->
        <div class="flex items-center gap-4">
            <!-- Notifications -->
            <button class="relative rounded-lg p-2 theme-muted-text hover-theme-muted">
                <i class="ti ti-bell text-2xl"></i>
                <span class="absolute right-1 top-1 flex size-2">
                    <span class="absolute inline-flex size-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex size-2 rounded-full bg-red-500"></span>
                </span>
            </button>

            <!-- Theme Toggle -->
            <button id="theme-toggle" class="rounded-lg p-2 theme-muted-text hover-theme-muted"
                onclick="window.__toggleDarkMode()">
                <i class="ti ti-moon text-2xl dark:hidden"></i>
                <i class="ti ti-sun hidden text-2xl dark:block"></i>
            </button>

            <!-- Import Button (hidden on small screens) placed at far right) -->
            @if (!in_array(Route::currentRouteName(), ['videos.import', 'videos.create', 'videos.traiter']))
                <a href="{{ route('videos.import') }}"
                    class="hidden lg:inline-flex items-center gap-2 rounded-lg bg-red-600 hover:bg-red-700 text-white px-3 py-1.5">
                    <i class="ti ti-download"></i>
                    Importer vidéo
                </a>
            @endif
        </div>
    </header>

</div>
