<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ARTS Construction & Services')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
@php
    $role = auth()->user()->role;
    $navigation = [
        ['label' => 'Tableau de bord', 'icon' => 'layout-dashboard', 'route' => 'dashboard', 'match' => 'dashboard', 'roles' => ['administrateur', 'chef_chantier', 'directeur', 'comptable']],
        ['label' => 'Chantiers', 'icon' => 'building-2', 'route' => 'chantiers.index', 'match' => 'chantiers.*', 'roles' => ['administrateur', 'chef_chantier', 'directeur', 'comptable']],
        ['label' => 'Dépenses', 'icon' => 'receipt', 'route' => 'depenses.index', 'match' => 'depenses.index', 'roles' => ['administrateur', 'chef_chantier', 'directeur', 'comptable']],
        ['label' => 'Rapports', 'icon' => 'chart-no-axes-column', 'route' => 'depenses.rapports', 'match' => 'depenses.rapports', 'roles' => ['administrateur', 'comptable']],
        ['label' => 'Utilisateurs', 'icon' => 'users-round', 'route' => 'users.index', 'match' => 'users.*', 'roles' => ['administrateur']],
    ];
    $adminNavigation = [
        ['label' => 'Fournisseurs', 'icon' => 'truck', 'route' => 'fournisseurs.index', 'match' => 'fournisseurs.*'],
        ['label' => 'Catégories', 'icon' => 'tag', 'route' => 'categories.index', 'match' => 'categories.*'],
        ['label' => 'Unités', 'icon' => 'scale', 'route' => 'unites.index', 'match' => 'unites.*'],
    ];
    $initialNotifications = auth()->user()->unreadNotifications()->latest()->limit(20)->get()->map(fn ($notification) => [
        'id' => $notification->id,
        'title' => $notification->data['title'] ?? 'Nouvelle notification',
        'message' => $notification->data['message'] ?? '',
        'url' => $notification->data['url'] ?? route('dashboard'),
        'created_at' => $notification->created_at?->diffForHumans(),
    ])->values();
@endphp
<body class="min-h-screen bg-[#F7F6F3] text-[#232323] antialiased" x-data="{ mobileNavOpen: false, profileOpen: false }" @keydown.escape.window="mobileNavOpen = false; profileOpen = false">
    <aside class="fixed inset-y-0 left-0 z-40 hidden w-60 flex-col border-r border-[#E7E5E1] bg-[#FCFBF9] md:flex">
        <a href="{{ route('dashboard') }}" class="flex h-26 items-center px-6" aria-label="ARTS, accueil">
            <img src="/images/arts-logo.png" class="h-16 w-auto" alt="ARTS Construction & Services">
        </a>
        <nav class="flex-1 space-y-1 px-4 pt-4" aria-label="Navigation principale">
            @foreach($navigation as $item)
                @continue(! in_array($role, $item['roles']))
                @php
                    $active = request()->routeIs($item['match']) || ($item['route'] === 'dashboard' && request()->is('dashboard')) || ($item['route'] === 'depenses.index' && request()->routeIs('depenses.*') && ! request()->routeIs('depenses.rapports'));
                @endphp
                <a href="{{ route($item['route']) }}" @if($active) aria-current="page" @endif
                   class="flex min-h-11 items-center gap-3 border-l-2 px-4 py-2.5 text-sm font-medium transition-colors {{ $active ? 'border-[#D7232A] bg-[#F8EEEE] text-[#C31F28]' : 'border-transparent text-[#444] hover:bg-[#F2F0EC] hover:text-[#232323]' }}">
                    <x-icon :name="$item['icon']" class="size-5" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
            @if($role === 'administrateur')
                <p class="mt-7 border-t border-[#E7E5E1] px-4 pt-6 text-[11px] font-semibold uppercase tracking-wider text-[#777]">Administration</p>
                @foreach($adminNavigation as $item)
                    @php $active = request()->routeIs($item['match']); @endphp
                    <a href="{{ route($item['route']) }}" @if($active) aria-current="page" @endif
                       class="flex min-h-11 items-center gap-3 border-l-2 px-4 py-2.5 text-sm font-medium transition-colors {{ $active ? 'border-[#D7232A] bg-[#F8EEEE] text-[#C31F28]' : 'border-transparent text-[#444] hover:bg-[#F2F0EC] hover:text-[#232323]' }}">
                        <x-icon :name="$item['icon']" class="size-5" />
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            @endif
        </nav>
    </aside>

    <div x-cloak x-show="mobileNavOpen" class="fixed inset-0 z-50 md:hidden" role="dialog" aria-modal="true" aria-label="Navigation">
        <div class="absolute inset-0 bg-[#232323]/45" @click="mobileNavOpen = false"></div>
        <aside x-trap.inert.noscroll="mobileNavOpen" class="relative flex h-full w-[min(18rem,85vw)] flex-col bg-[#FCFBF9] shadow-xl" @click.outside="mobileNavOpen = false">
            <div class="flex h-20 items-center justify-between px-5">
                <img src="/images/arts-logo.png" class="h-14 w-auto" alt="ARTS Construction & Services">
                <button type="button" @click="mobileNavOpen = false" aria-label="Fermer le menu" class="rounded-lg p-2 text-[#444] hover:bg-[#F2F0EC]"><x-icon name="x" class="size-5" /></button>
            </div>
            <nav class="space-y-1 px-4 pt-3" aria-label="Navigation mobile">
                @foreach($navigation as $item)
                    @continue(! in_array($role, $item['roles']))
                    @php
                        $active = request()->routeIs($item['match']) || ($item['route'] === 'dashboard' && request()->is('dashboard')) || ($item['route'] === 'depenses.index' && request()->routeIs('depenses.*') && ! request()->routeIs('depenses.rapports'));
                    @endphp
                    <a href="{{ route($item['route']) }}" @if($active) aria-current="page" @endif class="flex min-h-11 items-center gap-3 border-l-2 px-4 py-2.5 text-sm {{ $active ? 'border-[#D7232A] bg-[#F8EEEE] font-semibold text-[#C31F28]' : 'border-transparent text-[#444]' }}">
                        <x-icon :name="$item['icon']" class="size-5" />{{ $item['label'] }}
                    </a>
                @endforeach
                @if($role === 'administrateur')
                    <p class="mt-6 border-t border-[#E7E5E1] px-4 pt-5 text-[11px] font-semibold uppercase tracking-wider text-[#777]">Administration</p>
                    @foreach($adminNavigation as $item)
                        @php $active = request()->routeIs($item['match']); @endphp
                        <a href="{{ route($item['route']) }}" @if($active) aria-current="page" @endif class="flex min-h-11 items-center gap-3 border-l-2 px-4 py-2.5 text-sm {{ $active ? 'border-[#D7232A] bg-[#F8EEEE] font-semibold text-[#C31F28]' : 'border-transparent text-[#444]' }}">
                            <x-icon :name="$item['icon']" class="size-5" />{{ $item['label'] }}
                        </a>
                    @endforeach
                @endif
            </nav>
            <form action="{{ route('logout') }}" method="POST" class="mt-auto border-t border-[#E7E5E1] p-4">
                @csrf
                <button type="submit" class="flex min-h-11 w-full items-center gap-3 rounded-lg px-4 py-2.5 text-left text-sm font-medium text-[#444] hover:bg-[#F2F0EC]">
                    <x-icon name="log-out" class="size-5" />Déconnexion
                </button>
            </form>
        </aside>
    </div>

    <div class="min-h-screen md:pl-60">
        <header class="border-b border-[#E7E5E1] bg-[#FCFBF9]">
            <div class="mx-auto flex min-h-20 max-w-[1600px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-7">
                <div class="hidden min-w-0 md:block">
                    <h1 class="truncate text-2xl font-semibold tracking-tight text-[#232323]">@yield('header', 'Tableau de bord')</h1>
                    @hasSection('header-subtitle')<p class="mt-0.5 text-sm text-[#6D6D6D]">@yield('header-subtitle')</p>@endif
                </div>
                <a href="{{ route('dashboard') }}" class="md:hidden" aria-label="ARTS, accueil"><img src="/images/arts-logo.png" class="h-12 w-auto" alt="ARTS Construction & Services"></a>
                <div class="ml-auto flex items-center gap-3 sm:gap-5">
                    @if(request()->routeIs('dashboard') || request()->is('dashboard'))
                        <time datetime="{{ now()->toDateString() }}" class="hidden text-sm text-[#555] sm:inline">{{ now()->locale('fr')->isoFormat('D MMM YYYY') }}</time>
                    @endif
                    <div
                        class="relative"
                        x-data="notificationBell({
                            indexUrl: @js(route('notifications.index')),
                            readUrl: @js(route('notifications.read', '__ID__')),
                            readAllUrl: @js(route('notifications.read-all')),
                            notifications: @js($initialNotifications),
                            unreadCount: {{ auth()->user()->unreadNotifications()->count() }},
                        })"
                        @keydown.escape.window="open = false"
                    >
                        <button
                            type="button"
                            class="relative rounded-lg p-2 text-[#333] hover:bg-[#F2F0EC] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#D7232A]"
                            @click="open = !open; if (open) refresh()"
                            :aria-expanded="open"
                            aria-controls="notifications-menu"
                            aria-label="Ouvrir les notifications"
                        >
                            <x-icon name="bell" class="size-5" />
                            <span
                                x-cloak
                                x-show="unreadCount > 0"
                                x-text="unreadCount > 99 ? '99+' : unreadCount"
                                class="absolute -right-1 -top-1 flex min-h-5 min-w-5 items-center justify-center rounded-full bg-[#D7232A] px-1 text-[10px] font-bold text-white"
                                aria-live="polite"
                            ></span>
                        </button>
                        <div
                            id="notifications-menu"
                            x-cloak
                            x-show="open"
                            x-trap.inert="open"
                            @click.outside="open = false"
                            class="absolute right-0 top-full z-50 mt-3 w-[min(23rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-[#E7E5E1] bg-white shadow-xl"
                        >
                            <div class="flex items-center justify-between border-b border-[#E7E5E1] px-4 py-3">
                                <div>
                                    <p class="text-sm font-semibold">Notifications</p>
                                    <p class="text-xs text-[#777]" x-text="unreadCount ? `${unreadCount} non lue${unreadCount > 1 ? 's' : ''}` : 'Vous êtes à jour'"></p>
                                </div>
                                <button x-show="unreadCount > 0" type="button" @click="markAllRead()" class="text-xs font-semibold text-[#C31F28] hover:underline">Tout marquer comme lu</button>
                            </div>
                            <div class="max-h-96 overflow-y-auto" role="list">
                                <template x-if="notifications.length === 0">
                                    <div class="px-4 py-8 text-center text-sm text-[#777]">
                                        <x-icon name="check-check" class="mx-auto mb-2 size-6" />
                                        Aucune nouvelle notification
                                    </div>
                                </template>
                                <template x-for="notification in notifications" :key="notification.id">
                                    <button type="button" @click="openNotification(notification)" class="block w-full border-b border-[#F0EEEA] px-4 py-3 text-left last:border-0 hover:bg-[#FAF9F7]" role="listitem">
                                        <span class="block text-sm font-semibold text-[#232323]" x-text="notification.title"></span>
                                        <span class="mt-1 block text-xs leading-5 text-[#555]" x-text="notification.message"></span>
                                        <span class="mt-1 block text-[11px] text-[#888]" x-text="notification.created_at"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="relative hidden border-l border-[#E7E5E1] pl-5 md:block">
                        <button type="button" @click="profileOpen = !profileOpen" :aria-expanded="profileOpen" aria-label="Ouvrir le menu du profil" class="flex items-center gap-3 rounded-md text-left focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#D7232A]">
                            <span class="flex size-9 items-center justify-center rounded-full bg-[#D7232A] text-sm font-semibold text-white">{{ mb_substr(auth()->user()->name ?? 'U', 0, 1) }}</span>
                            <span class="hidden lg:block"><span class="block text-sm font-semibold leading-tight">{{ auth()->user()->name }}</span><span class="block text-xs text-[#777]">{{ ucfirst(str_replace('_', ' ', $role)) }}</span></span>
                            <x-icon name="chevron-down" class="size-4 text-[#777]" />
                        </button>
                        <div x-cloak x-show="profileOpen" @click.outside="profileOpen = false" class="absolute right-0 top-full z-50 mt-3 w-48 rounded-xl border border-[#E7E5E1] bg-white p-1.5 shadow-lg">
                            <form action="{{ route('logout') }}" method="POST">@csrf
                                <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left text-sm hover:bg-[#F7F6F3]"><x-icon name="log-out" class="size-4" />Déconnexion</button>
                            </form>
                        </div>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-[#333] hover:bg-[#F2F0EC] md:hidden" @click="mobileNavOpen = true" aria-label="Ouvrir le menu" :aria-expanded="mobileNavOpen"><x-icon name="menu" class="size-6" /></button>
                </div>
            </div>
        </header>
        <main class="mx-auto max-w-[1600px] p-4 sm:p-6 lg:p-7">@yield('content')</main>
    </div>
</body>
</html>
