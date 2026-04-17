<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'StratAction Consulting')</title>
    
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js - Version stable -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        
        /* Animations fluides */
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.4s ease-out;
        }
        
        /* Hover amélioré pour les cartes */
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        /* Effet de ligne de tableau */
        tbody tr {
            transition: all 0.2s ease;
        }
        
        tbody tr:hover {
            background: linear-gradient(to right, rgba(227, 30, 36, 0.03), transparent);
            transform: scale(1.01);
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased" x-data="{ isSidebarOpen: true }">
    
    <!-- Sidebar -->
    <div :class="isSidebarOpen ? 'w-64' : 'w-20'" class="fixed inset-y-0 left-0 bg-[#3D3D3D] text-white transition-all duration-150 z-50 hidden md:block">
        <div class="flex items-center justify-center h-24 px-4 border-b border-white/10">
            <div class="w-full flex justify-center" x-show="isSidebarOpen" x-transition>
                <img src="/images/arts-logo.png" class="h-16 w-auto" alt="ARTS Constructions & Services">
            </div>
            <button @click="isSidebarOpen = !isSidebarOpen" class="p-2 hover:bg-white/10 rounded-lg" x-show="!isSidebarOpen">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
        
        <!-- Toggle button when open -->
        <div class="px-4 py-2" x-show="isSidebarOpen">
            <button @click="isSidebarOpen = !isSidebarOpen" class="w-full p-2 hover:bg-white/10 rounded-lg text-center">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
        
        <nav class="mt-6 px-4 space-y-2">
            <!-- Dashboard - Visible à tous -->
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 p-3 {{ request()->routeIs('dashboard') ? 'bg-[#E31E24]' : 'hover:bg-white/10' }} rounded-xl text-white transition">
                <i class="fa-solid fa-chart-line w-6"></i>
                <span x-show="isSidebarOpen">Dashboard</span>
            </a>

            <!-- Chantiers - Admin, Chef, Directeur -->
            @if(in_array(auth()->user()->role, ['administrateur', 'chef_chantier', 'directeur']))
            <a href="{{ route('chantiers.index') }}" class="flex items-center space-x-3 p-3 {{ request()->routeIs('chantiers.*') ? 'bg-[#E31E24]' : 'hover:bg-white/10' }} rounded-xl text-white transition">
                <i class="fa-solid fa-building w-6"></i>
                <span x-show="isSidebarOpen">Chantiers</span>
            </a>
            @endif

            <!-- Dépenses - Tous sauf comptable (qui voit depuis dashboard) -->
            @if(in_array(auth()->user()->role, ['administrateur', 'chef_chantier', 'directeur', 'comptable']))
            <a href="{{ route('depenses.index') }}" class="flex items-center space-x-3 p-3 {{ request()->routeIs('depenses.index') || request()->routeIs('depenses.show') || request()->routeIs('depenses.create') || request()->routeIs('depenses.edit') ? 'bg-[#E31E24]' : 'hover:bg-white/10' }} rounded-xl text-white transition">
                <i class="fa-solid fa-money-bill-transfer w-6"></i>
                <span x-show="isSidebarOpen">Dépenses</span>
            </a>
            @endif

            <!-- Rapports - Admin et Comptable -->
            @if(in_array(auth()->user()->role, ['administrateur', 'comptable']))
            <a href="{{ route('depenses.rapports') }}" class="flex items-center space-x-3 p-3 {{ request()->routeIs('depenses.rapports') ? 'bg-[#E31E24]' : 'hover:bg-white/10' }} rounded-xl text-white transition">
                <i class="fa-solid fa-chart-bar w-6"></i>
                <span x-show="isSidebarOpen">Rapports</span>
            </a>
            @endif

            <!-- Utilisateurs - Admin seulement -->
            @if(auth()->user()->role === 'administrateur')
            <a href="{{ route('users.index') }}" class="flex items-center space-x-3 p-3 {{ request()->routeIs('users.*') ? 'bg-[#E31E24]' : 'hover:bg-white/10' }} rounded-xl text-white transition">
                <i class="fa-solid fa-users w-6"></i>
                <span x-show="isSidebarOpen">Utilisateurs</span>
            </a>
            @endif

            <!-- Admin Section - Admin seulement -->
            @if(auth()->user()->role === 'administrateur')
            <div class="pt-4 mt-4 border-t border-white/10">
                <p class="text-xs font-bold text-gray-300 uppercase px-3 mb-3" x-show="isSidebarOpen">Administration</p>
                <a href="{{ route('categories.index') }}" class="flex items-center space-x-3 p-3 {{ request()->routeIs('categories.*') ? 'bg-[#E31E24]' : 'hover:bg-white/10' }} rounded-xl text-white transition text-sm">
                    <i class="fa-solid fa-tag w-6"></i>
                    <span x-show="isSidebarOpen">Catégories</span>
                </a>
                <a href="{{ route('unites.index') }}" class="flex items-center space-x-3 p-3 {{ request()->routeIs('unites.*') ? 'bg-[#E31E24]' : 'hover:bg-white/10' }} rounded-xl text-white transition text-sm">
                    <i class="fa-solid fa-scale-balanced w-6"></i>
                    <span x-show="isSidebarOpen">Unités</span>
                </a>
            </div>
            @endif
        </nav>
    </div>

    <!-- Main Content -->
    <div :class="isSidebarOpen ? 'md:ml-64' : 'md:ml-20'" class="transition-all duration-150 min-h-screen">
        <!-- Header -->
        <header class="h-20 bg-white shadow-sm flex items-center justify-between px-8 sticky top-0 z-40">
            <h1 class="text-xl font-bold text-gray-800">@yield('header', 'Tableau de Bord')</h1>
            
            <div class="flex items-center space-x-4">
                <button class="p-2 text-gray-400 hover:text-[#E31E24] transition">
                    <i class="fa-solid fa-bell"></i>
                </button>
                <div class="flex items-center space-x-3 border-l pl-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-bold text-gray-900">{{ auth()->user()->name ?? 'Utilisateur' }}</p>
                        <p class="text-xs text-gray-500">{{ ucfirst(auth()->user()->role ?? 'Invité') }}</p>
                    </div>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="w-10 h-10 bg-[#E31E24] rounded-full flex items-center justify-center text-white font-bold hover:bg-[#C71A20] transition">
                            {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                        </button>
                        
                        <div x-show="open" @click.away="open = false" x-cloak
                            class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-[#E31E24]">
                                <i class="fa-solid fa-user-gear mr-2"></i> Profil
                            </a>
                            <hr class="my-2 border-gray-100">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <i class="fa-solid fa-right-from-bracket mr-2"></i> Déconnexion
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="p-8">
            @yield('content')
        </main>
    </div>

</body>
</html>
