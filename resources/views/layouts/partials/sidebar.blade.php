<!-- Sidebar -->
<aside id="sidebar"
    class="fixed inset-y-0 left-0 z-50 w-64 transform theme-surface  transition-transform duration-300 ease-in-out lg:relative lg:translate-x-0 flex flex-col"
    style="transform: translateX(-100%);">
    <!-- Sidebar Header -->
    <div class="flex h-16 items-center justify-between border-b theme-divider px-6">
        <a href="{{ route('home') }}" class="flex items-center gap-2 text-xl font-bold theme-title">
            <i class="ti ti-brand-youtube text-red-600"></i>
            <span>Traitement Video</span>
        </a>
        <button onclick="window.__toggleSidebar()" class="theme-muted-text hover-theme-muted rounded-lg p-2 lg:hidden">
            <i class="ti ti-x text-2xl"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        <a href="{{ route('home') }}"
            class="app-nav-item flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition theme-body hover-theme-muted"
            data-route="home">
            <i class="ti ti-dashboard text-xl"></i>
            <span>Tableau de bord</span>
        </a>

        <div class="pb-2 pt-4">
            <h3 class="px-3 text-xs font-semibold uppercase tracking-wider theme-muted-text">
                Gestion
            </h3>
        </div>

        <a href="{{ route('entites.index') }}" data-route="entites"
            class="app-nav-item flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition theme-body hover-theme-muted">
            <i class="ti ti-users text-xl"></i>
            <span>Équipes / Entités</span>
        </a>

        <a href="{{ route('settings.index') }}" data-route="settings"
            class="app-nav-item flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition theme-body hover-theme-muted">
            <i class="ti ti-settings text-xl"></i>
            <span>Paramètres</span>
        </a>

        <a href="{{ route('keys.index') }}" data-route="keys"
            class="app-nav-item flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition theme-body hover-theme-muted">
            <i class="ti ti-key text-xl"></i>
            <span>Clés d'API</span>
        </a>

        <div class="pb-2 pt-4">
            <h3 class="px-3 text-xs font-semibold uppercase tracking-wider theme-muted-text">
                Contenu
            </h3>
        </div>

        <a href="{{ route('videos.index') }}" data-route="videos"
            class="app-nav-item flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition theme-body hover-theme-muted">
            <i class="ti ti-video text-xl"></i>
            <span>Vidéos</span>
        </a>

        <a href="{{ route('chaines.index') }}" data-route="chaines"
            class="app-nav-item flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition theme-body hover-theme-muted">
            <i class="ti ti-brand-youtube text-xl"></i>
            <span>Chaînes</span>
        </a>

        <a href="{{ route('promptes.index') }}" data-route="promptes"
            class="app-nav-item flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition theme-body hover-theme-muted">
            <i class="ti ti-forms text-xl"></i>
            <span>Promptes</span>
        </a>

        <a href="#"
            class="app-nav-item flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition theme-body hover-theme-muted">
            <i class="ti ti-file-text text-xl"></i>
            <span>Articles</span>
        </a>

        <a href="#"
            class="app-nav-item flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition theme-body hover-theme-muted">
            <i class="ti ti-photo text-xl"></i>
            <span>Médias</span>
        </a>
    </nav>

    <!-- Sidebar Footer (sticky bottom) -->
    <div class="mt-auto border-t theme-divider p-4">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-full bg-red-600 text-white">
                <span class="font-semibold">{{ substr(auth()->user()->name ?? 'U', 0, 1) }}</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium theme-title">
                    {{ auth()->user()->name ?? 'Utilisateur' }}
                </p>
                <p class="truncate text-xs theme-muted-text">
                    {{ auth()->user()->email ?? 'user@example.com' }}
                </p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg p-2 theme-muted-text hover-theme-muted" title="Déconnexion">
                    <i class="ti ti-logout text-xl"></i>
                </button>
            </form>
        </div>
    </div>
</aside>
