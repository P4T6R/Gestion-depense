@extends('layouts.admin')

@section('title', 'Dashboard - StratAction Consulting')
@section('header', 'Tableau de Bord')

@section('content')
    @if(auth()->user()->role === 'administrateur' || auth()->user()->role === 'directeur')
        @php
            $alertes = $chantiersActifs->filter(fn($c) => in_array($c->statut_budget, ['critique', 'depassement']));
        @endphp
        
        @if($alertes->count() > 0)
        <div class="mb-6 animate-pulse">
            <div class="bg-red-50 border-l-4 border-red-600 p-4 rounded-r-xl shadow-md">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-triangle-exclamation text-red-600 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700 font-bold">
                            ALERTE BUDGET : {{ $alertes->count() }} chantier(s) nécessite(nt) votre attention immédiate.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endif

    <!-- DASHBOARD ADMINISTRATEUR -->
    @if(auth()->user()->role === 'administrateur')
        <!-- Première Ligne: KPIs Principaux -->
        <div class="grid md:grid-cols-4 gap-6 mb-6">
            <!-- Utilisateurs -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Utilisateurs</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total_utilisateurs'] }}</p>
                    </div>
                    <div class="bg-blue-100 p-4 rounded-xl">
                        <i class="fa-solid fa-users text-2xl text-blue-500"></i>
                    </div>
                </div>
            </div>

            <!-- Chantiers -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-indigo-500 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Chantiers</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total_chantiers'] }}</p>
                    </div>
                    <div class="bg-indigo-100 p-4 rounded-xl">
                        <i class="fa-solid fa-building text-2xl text-indigo-500"></i>
                    </div>
                </div>
            </div>

            <!-- Dépenses Total -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Dépenses (Nb)</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total_depenses'] }}</p>
                    </div>
                    <div class="bg-green-100 p-4 rounded-xl">
                        <i class="fa-solid fa-list-check text-2xl text-green-500"></i>
                    </div>
                </div>
            </div>

            <!-- Montant Total -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-purple-500 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Montant Total</p>
                        <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_montant_depenses'], 0, ',', ' ') }} F</p>
                    </div>
                    <div class="bg-purple-100 p-4 rounded-xl">
                        <i class="fa-solid fa-money-bill-wave text-2xl text-purple-500"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deuxième Ligne: Statuts des Dépenses -->
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Détail des Statuts des Dépenses</h2>
            <div class="grid md:grid-cols-4 gap-6">
                <!-- En Attente -->
                <div class="bg-yellow-50 rounded-2xl shadow-lg p-6 border-l-4 border-yellow-500 hover:shadow-xl transition-all">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-gray-600 font-medium">En Attente</p>
                            <i class="fa-solid fa-hourglass-half text-yellow-500"></i>
                        </div>
                        <p class="text-2xl font-bold text-yellow-700">{{ $stats['depenses_en_attente'] }}</p>
                        <p class="text-sm text-gray-600">{{ number_format($stats['montant_en_attente'], 0, ',', ' ') }} F</p>
                    </div>
                </div>

                <!-- Acceptées -->
                <div class="bg-blue-50 rounded-2xl shadow-lg p-6 border-l-4 border-[#E31E24] hover:shadow-xl transition-all">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-gray-600 font-medium">Acceptées</p>
                            <i class="fa-solid fa-thumbs-up text-[#E31E24]"></i>
                        </div>
                        <p class="text-2xl font-bold text-blue-700">{{ $stats['depenses_acceptees'] }}</p>
                        <p class="text-sm text-gray-600">{{ number_format($stats['montant_acceptees'], 0, ',', ' ') }} F</p>
                    </div>
                </div>

                <!-- Validées -->
                <div class="bg-green-50 rounded-2xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-all">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-gray-600 font-medium">Validées</p>
                            <i class="fa-solid fa-check-circle text-green-500"></i>
                        </div>
                        <p class="text-2xl font-bold text-green-700">{{ $stats['depenses_validees'] }}</p>
                        <p class="text-sm text-gray-600">{{ number_format($stats['montant_validees'], 0, ',', ' ') }} F</p>
                    </div>
                </div>

                <!-- Rejetées -->
                <div class="bg-red-50 rounded-2xl shadow-lg p-6 border-l-4 border-red-500 hover:shadow-xl transition-all">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-gray-600 font-medium">Rejetées</p>
                            <i class="fa-solid fa-times-circle text-red-500"></i>
                        </div>
                        <p class="text-2xl font-bold text-red-700">{{ $stats['depenses_rejetees'] }}</p>
                        <p class="text-sm text-gray-600">{{ number_format($stats['montant_rejetees'], 0, ',', ' ') }} F</p>
                    </div>
                </div>
            </div>
        </div>

    <!-- DASHBOARD CHEF DE CHANTIER -->
    @elseif(auth()->user()->role === 'chef_chantier')
        <div class="grid md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-[#E31E24] hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Mes Chantiers</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['mes_chantiers'] }}</p>
                    </div>
                    <div class="bg-blue-100 p-4 rounded-xl">
                        <i class="fa-solid fa-building text-2xl text-[#E31E24]"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Mes Dépenses</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['mes_depenses'] }}</p>
                    </div>
                    <div class="bg-green-100 p-4 rounded-xl">
                        <i class="fa-solid fa-list-check text-2xl text-green-500"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-purple-500 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Montant Total</p>
                        <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($stats['montant_total'], 0, ',', ' ') }} F</p>
                    </div>
                    <div class="bg-purple-100 p-4 rounded-xl">
                        <i class="fa-solid fa-money-bill-wave text-2xl text-purple-500"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-yellow-500 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Budget Utilisé</p>
                        <p class="text-2xl font-bold text-gray-900 mt-2">
                            @php
                                $totalChantiers = $chantiersActifs->sum('budget_alloue');
                                $percentage = $totalChantiers > 0 ? ($stats['montant_total'] / $totalChantiers) * 100 : 0;
                            @endphp
                            {{ number_format($percentage, 1) }}%
                        </p>
                    </div>
                    <div class="bg-yellow-100 p-4 rounded-xl">
                        <i class="fa-solid fa-chart-pie text-2xl text-yellow-500"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Mes Dépenses par Statut</h2>
            <div class="grid md:grid-cols-4 gap-6">
                <div class="bg-yellow-50 rounded-2xl shadow-lg p-6 border-l-4 border-yellow-500">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-gray-600 font-medium">En Attente</p>
                            <i class="fa-solid fa-hourglass-half text-yellow-500"></i>
                        </div>
                        <p class="text-2xl font-bold text-yellow-700">{{ $stats['depenses_en_attente'] }}</p>
                        <p class="text-sm text-gray-600">{{ number_format($stats['montant_en_attente'], 0, ',', ' ') }} F</p>
                    </div>
                </div>

                <div class="bg-blue-50 rounded-2xl shadow-lg p-6 border-l-4 border-[#E31E24]">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-gray-600 font-medium">Acceptées</p>
                            <i class="fa-solid fa-thumbs-up text-[#E31E24]"></i>
                        </div>
                        <p class="text-2xl font-bold text-blue-700">{{ $stats['depenses_acceptees'] }}</p>
                        <p class="text-sm text-gray-600">{{ number_format($stats['montant_acceptees'], 0, ',', ' ') }} F</p>
                    </div>
                </div>

                <div class="bg-green-50 rounded-2xl shadow-lg p-6 border-l-4 border-green-500">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-gray-600 font-medium">Validées</p>
                            <i class="fa-solid fa-check-circle text-green-500"></i>
                        </div>
                        <p class="text-2xl font-bold text-green-700">{{ $stats['depenses_validees'] }}</p>
                        <p class="text-sm text-gray-600">{{ number_format($stats['montant_validees'], 0, ',', ' ') }} F</p>
                    </div>
                </div>

                <div class="bg-red-50 rounded-2xl shadow-lg p-6 border-l-4 border-red-500">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-gray-600 font-medium">Rejetées</p>
                            <i class="fa-solid fa-times-circle text-red-500"></i>
                        </div>
                        <p class="text-2xl font-bold text-red-700">{{ $stats['depenses_rejetees'] }}</p>
                        <p class="text-sm text-gray-600">{{ number_format($stats['montant_rejetees'], 0, ',', ' ') }} F</p>
                    </div>
                </div>
            </div>
        </div>

    <!-- DASHBOARD DIRECTEUR -->
    @elseif(auth()->user()->role === 'directeur')
        <div class="grid md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-[#E31E24] hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Total Chantiers</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total_chantiers'] }}</p>
                    </div>
                    <div class="bg-blue-100 p-4 rounded-xl">
                        <i class="fa-solid fa-building text-2xl text-[#E31E24]"></i>
                    </div>
                </div>
            </div>

            <div class="bg-yellow-50 rounded-2xl shadow-lg p-6 border-l-4 border-yellow-500 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">À Approuver</p>
                        <p class="text-3xl font-bold text-yellow-700 mt-2">{{ $stats['a_approuver'] }}</p>
                        <p class="text-sm text-gray-600 mt-1">{{ number_format($stats['montant_a_approuver'], 0, ',', ' ') }} F</p>
                    </div>
                    <div class="bg-yellow-100 p-4 rounded-xl">
                        <i class="fa-solid fa-hourglass-half text-2xl text-yellow-500"></i>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 rounded-2xl shadow-lg p-6 border-l-4 border-[#E31E24] hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Approuvées</p>
                        <p class="text-3xl font-bold text-blue-700 mt-2">{{ $stats['approuvees'] }}</p>
                        <p class="text-sm text-gray-600 mt-1">{{ number_format($stats['montant_approuvees'], 0, ',', ' ') }} F</p>
                    </div>
                    <div class="bg-blue-100 p-4 rounded-xl">
                        <i class="fa-solid fa-check-circle text-2xl text-[#E31E24]"></i>
                    </div>
                </div>
            </div>

            <div class="bg-green-50 rounded-2xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Validées</p>
                        <p class="text-3xl font-bold text-green-700 mt-2">{{ $stats['validees'] }}</p>
                        <p class="text-sm text-gray-600 mt-1">{{ number_format($stats['montant_validees'], 0, ',', ' ') }} F</p>
                    </div>
                    <div class="bg-green-100 p-4 rounded-xl">
                        <i class="fa-solid fa-stamp text-2xl text-green-500"></i>
                    </div>
                </div>
            </div>
        </div>

    <!-- DASHBOARD COMPTABLE -->
    @elseif(auth()->user()->role === 'comptable')
        <div class="grid md:grid-cols-4 gap-6 mb-6">
            <div class="bg-yellow-50 rounded-2xl shadow-lg p-6 border-l-4 border-yellow-500 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">À Valider</p>
                        <p class="text-3xl font-bold text-yellow-700 mt-2">{{ $stats['a_valider'] }}</p>
                        <p class="text-sm text-gray-600 mt-1">{{ number_format($stats['montant_a_valider'], 0, ',', ' ') }} F</p>
                    </div>
                    <div class="bg-yellow-100 p-4 rounded-xl">
                        <i class="fa-solid fa-hourglass-half text-2xl text-yellow-500"></i>
                    </div>
                </div>
            </div>

            <div class="bg-green-50 rounded-2xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Validées</p>
                        <p class="text-3xl font-bold text-green-700 mt-2">{{ $stats['validees'] }}</p>
                        <p class="text-sm text-gray-600 mt-1">{{ number_format($stats['montant_validees'], 0, ',', ' ') }} F</p>
                    </div>
                    <div class="bg-green-100 p-4 rounded-xl">
                        <i class="fa-solid fa-check-circle text-2xl text-green-500"></i>
                    </div>
                </div>
            </div>

            <div class="bg-red-50 rounded-2xl shadow-lg p-6 border-l-4 border-red-500 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Rejetées</p>
                        <p class="text-3xl font-bold text-red-700 mt-2">{{ $stats['rejetees'] }}</p>
                        <p class="text-sm text-gray-600 mt-1">{{ number_format($stats['montant_rejetees'], 0, ',', ' ') }} F</p>
                    </div>
                    <div class="bg-red-100 p-4 rounded-xl">
                        <i class="fa-solid fa-times-circle text-2xl text-red-500"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-indigo-500 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Total Validé</p>
                        <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($stats['montant_validees'], 0, ',', ' ') }} F</p>
                    </div>
                    <div class="bg-indigo-100 p-4 rounded-xl">
                        <i class="fa-solid fa-money-bill-wave text-2xl text-[#E31E24]"></i>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-8">
        <!-- Dernières Dépenses -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Dernières Dépenses</h2>
                <a href="{{ route('depenses.index') }}" class="text-[#E31E24] hover:text-[#E31E24] font-semibold text-sm">
                    Voir tout →
                </a>
            </div>

            <div class="space-y-4">
                @forelse($depensesRecentes as $depense)
                <div class="flex items-center justify-between p-4 border border-gray-100 rounded-xl hover:shadow-md transition-all">
                    <div class="flex items-center flex-1">
                        <div class="w-10 h-10 bg-gradient-to-br from-[#E31E24] to-[#C71A20] rounded-full flex items-center justify-center text-white font-bold mr-3 shadow-sm">
                            {{ substr($depense->utilisateur->name, 0, 1) }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center">
                                <p class="font-bold text-gray-900 text-sm mr-2">{{ $depense->nom_produit }}</p>
                                @php
                                    $statusColors = [
                                        'en_attente' => 'bg-yellow-100 text-yellow-700',
                                        'acceptee' => 'bg-blue-100 text-blue-700',
                                        'validee' => 'bg-green-100 text-green-700',
                                        'rejetee' => 'bg-red-100 text-red-700',
                                    ];
                                    $statusLabels = [
                                        'en_attente' => 'Attente',
                                        'acceptee' => 'Approuvée',
                                        'validee' => 'Validée',
                                        'rejetee' => 'Rejetée',
                                    ];
                                @endphp
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase {{ $statusColors[$depense->statut_validation] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $statusLabels[$depense->statut_validation] ?? $depense->statut_validation }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500">{{ $depense->chantier->nom }} • {{ $depense->utilisateur->name }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-black text-gray-900">{{ number_format($depense->prix_total, 0, ',', ' ') }} F</p>
                        <span class="text-[10px] text-gray-400 font-bold uppercase">{{ $depense->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">Aucune dépense récente.</p>
                @endforelse
            </div>
        </div>

        <!-- Chantiers Actifs -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Suivi des Budgets</h2>
                <a href="{{ route('chantiers.index') }}" class="text-[#E31E24] hover:text-[#E31E24] font-semibold text-sm">
                    Voir tout →
                </a>
            </div>

            <div class="space-y-6">
                @forelse($chantiersActifs as $chantier)
                @php
                    $pourcentage = $chantier->pourcentage_consomme;
                    $statut = $chantier->statut_budget;
                    $colorClass = [
                        'sain' => 'bg-green-500',
                        'attention' => 'bg-yellow-500',
                        'critique' => 'bg-orange-500',
                        'depassement' => 'bg-red-600'
                    ][$statut] ?? 'bg-[#E31E24]';
                    
                    $textClass = [
                        'sain' => 'text-green-600',
                        'attention' => 'text-yellow-600',
                        'critique' => 'text-orange-600',
                        'depassement' => 'text-red-600'
                    ][$statut] ?? 'text-[#E31E24]';

                    $label = [
                        'sain' => 'SAIN',
                        'attention' => 'ATTENTION',
                        'critique' => 'CRITIQUE',
                        'depassement' => 'DÉPASSEMENT'
                    ][$statut] ?? 'INCONNU';
                @endphp
                <div class="p-4 border border-gray-100 rounded-xl hover:shadow-md transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <div class="w-10 h-10 {{ $colorClass }} rounded-lg flex items-center justify-center mr-3 shadow-sm">
                                <i class="fa-solid fa-building text-white"></i>
                            </div>
                            <div>
                                <p class="font-bold text-gray-900">{{ $chantier->nom }}</p>
                                <p class="text-xs text-gray-500">{{ $chantier->lieu }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-black px-2 py-1 rounded {{ str_replace('bg-', 'bg-opacity-10 text-', $colorClass) }} {{ $textClass }}">
                                {{ $label }}
                            </span>
                            <p class="text-sm font-bold text-gray-900 mt-1">{{ number_format($pourcentage, 1) }}%</p>
                        </div>
                    </div>
                    
                    <!-- Barre de progression -->
                    <div class="w-full bg-gray-100 rounded-full h-2.5 mb-2">
                        <div class="{{ $colorClass }} h-2.5 rounded-full transition-all duration-500" style="width: {{ min($pourcentage, 100) }}%"></div>
                    </div>
                    
                    <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider">
                        <div class="text-gray-500">
                            Consommé: <span class="text-gray-900">{{ number_format($chantier->budget_consomme, 0, ',', ' ') }} F</span>
                        </div>
                        <div class="text-gray-500">
                            Restant: <span class="{{ $pourcentage > 100 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($chantier->budget_restant, 0, ',', ' ') }} F</span>
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">Aucun chantier actif.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Actions rapides (Admin seulement) -->
    @if(auth()->user()->role === 'administrateur')
    <div class="mt-8 grid md:grid-cols-3 gap-6">
        <a href="{{ route('chantiers.create') }}" class="bg-gradient-to-br from-[#E31E24] to-[#C71A20] text-white rounded-2xl p-8 hover:shadow-2xl hover:scale-105 transition-all group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold mb-2">Nouveau Chantier</h3>
                    <p class="text-blue-100">Créer un nouveau chantier</p>
                </div>
                <i class="fa-solid fa-plus text-4xl text-white/50 group-hover:scale-110 transition-transform"></i>
            </div>
        </a>

        <a href="{{ route('users.create') }}" class="bg-gradient-to-br from-[#E31E24] to-[#C71A20] text-white rounded-2xl p-8 hover:shadow-2xl hover:scale-105 transition-all group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold mb-2">Gérer Utilisateurs</h3>
                    <p class="text-blue-100">Ajouter ou modifier des utilisateurs</p>
                </div>
                <i class="fa-solid fa-users text-4xl text-white/50 group-hover:scale-110 transition-transform"></i>
            </div>
        </a>

        <a href="{{ route('depenses.rapports') }}" class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-2xl p-8 hover:shadow-2xl hover:scale-105 transition-all group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold mb-2">Rapports</h3>
                    <p class="text-green-100">Voir les statistiques détaillées</p>
                </div>
                <i class="fa-solid fa-chart-pie text-4xl text-white/50 group-hover:scale-110 transition-transform"></i>
            </div>
        </a>
    </div>
    @endif
@endsection
