@extends('layouts.admin')

@section('title', 'Dashboard - StratAction Consulting')
@section('header', auth()->user()?->role === 'administrateur' ? 'Vue d’ensemble' : 'Tableau de bord')
@if(auth()->user()?->role === 'administrateur')
@section('header-subtitle', 'Suivi de vos chantiers et dépenses')
@endif

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
                        <x-icon name="triangle-alert" class="text-red-600 size-5" />
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

    @if(auth()->user()->role === 'administrateur')
        @include('dashboard.admin')

    <!-- DASHBOARD CHEF DE CHANTIER -->
    @elseif(auth()->user()->role === 'chef_chantier')
        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Mes Chantiers</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['mes_chantiers'] }}</p>
                    </div>
                    <div class="rounded-xl bg-[#F8EEEE] p-3">
                        <x-icon name="building-2" class="size-6 text-[#D7232A]" />
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Mes Dépenses</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['mes_depenses'] }}</p>
                    </div>
                    <div class="rounded-xl bg-[#F8EEEE] p-3">
                        <x-icon name="list-checks" class="size-6 text-[#D7232A]" />
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Montant déclaré</p>
                        <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($stats['montant_total'], 0, ',', ' ') }} F</p>
                    </div>
                    <div class="rounded-xl bg-[#F8EEEE] p-3">
                        <x-icon name="wallet" class="size-6 text-[#D7232A]" />
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Budget Utilisé</p>
                        <p class="text-2xl font-bold text-gray-900 mt-2">
                            @php
                                $totalChantiers = $chantiersActifs->sum('budget_alloue');
                                $percentage = $totalChantiers > 0 ? ($chantiersActifs->sum('budget_consomme') / $totalChantiers) * 100 : 0;
                            @endphp
                            {{ number_format($percentage, 1) }}%
                        </p>
                    </div>
                    <div class="rounded-xl bg-[#F8EEEE] p-3">
                        <x-icon name="chart-pie" class="size-6 text-[#D7232A]" />
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
                            <x-icon name="hourglass" class="text-[#D7232A]" />
                        </div>
                        <p class="text-2xl font-bold text-yellow-700">{{ $stats['depenses_en_attente'] }}</p>
                        <p class="text-sm text-gray-600">{{ number_format($stats['montant_en_attente'], 0, ',', ' ') }} F</p>
                    </div>
                </div>

                <div class="bg-blue-50 rounded-2xl shadow-lg p-6 border-l-4 border-[#E31E24]">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-gray-600 font-medium">Acceptées</p>
                            <x-icon name="thumbs-up" class="text-[#D7232A]" />
                        </div>
                        <p class="text-2xl font-bold text-blue-700">{{ $stats['depenses_acceptees'] }}</p>
                        <p class="text-sm text-gray-600">{{ number_format($stats['montant_acceptees'], 0, ',', ' ') }} F</p>
                    </div>
                </div>

                <div class="bg-green-50 rounded-2xl shadow-lg p-6 border-l-4 border-green-500">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-gray-600 font-medium">Validées</p>
                            <x-icon name="circle-check" class="text-green-500" />
                        </div>
                        <p class="text-2xl font-bold text-green-700">{{ $stats['depenses_validees'] }}</p>
                        <p class="text-sm text-gray-600">{{ number_format($stats['montant_validees'], 0, ',', ' ') }} F</p>
                    </div>
                </div>

                <div class="bg-red-50 rounded-2xl shadow-lg p-6 border-l-4 border-red-500">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-gray-600 font-medium">Rejetées</p>
                            <x-icon name="circle-x" class="text-red-500" />
                        </div>
                        <p class="text-2xl font-bold text-red-700">{{ $stats['depenses_rejetees'] }}</p>
                        <p class="text-sm text-gray-600">{{ number_format($stats['montant_rejetees'], 0, ',', ' ') }} F</p>
                    </div>
                </div>
            </div>
        </div>

    <!-- DASHBOARD DIRECTEUR -->
    @elseif(auth()->user()->role === 'directeur')
        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Total Chantiers</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total_chantiers'] }}</p>
                    </div>
                    <div class="rounded-xl bg-[#F8EEEE] p-3">
                        <x-icon name="building-2" class="size-6 text-[#D7232A]" />
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">À Approuver</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['a_approuver'] }}</p>
                        <p class="text-sm text-gray-600 mt-1">{{ number_format($stats['montant_a_approuver'], 0, ',', ' ') }} F</p>
                    </div>
                    <div class="rounded-xl bg-[#F8EEEE] p-3">
                        <x-icon name="hourglass" class="size-6 text-[#D7232A]" />
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Approuvées</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['approuvees'] }}</p>
                        <p class="text-sm text-gray-600 mt-1">{{ number_format($stats['montant_approuvees'], 0, ',', ' ') }} F</p>
                        <p class="text-xs text-gray-500 mt-1">En attente de la comptable</p>
                    </div>
                    <div class="rounded-xl bg-[#F8EEEE] p-3">
                        <x-icon name="circle-check" class="size-6 text-[#D7232A]" />
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Validées</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['validees'] }}</p>
                        <p class="text-sm text-gray-600 mt-1">{{ number_format($stats['montant_validees'], 0, ',', ' ') }} F</p>
                    </div>
                    <div class="rounded-xl bg-[#F8EEEE] p-3">
                        <x-icon name="stamp" class="size-6 text-[#D7232A]" />
                    </div>
                </div>
            </div>
        </div>

    <!-- DASHBOARD COMPTABLE -->
    @elseif(auth()->user()->role === 'comptable')
        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">À Valider</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['a_valider'] }}</p>
                        <p class="text-sm text-gray-600 mt-1">{{ number_format($stats['montant_a_valider'], 0, ',', ' ') }} F</p>
                    </div>
                    <div class="rounded-xl bg-[#F8EEEE] p-3">
                        <x-icon name="hourglass" class="size-6 text-[#D7232A]" />
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Validées</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['validees'] }}</p>
                        <p class="text-sm text-gray-600 mt-1">{{ number_format($stats['montant_validees'], 0, ',', ' ') }} F</p>
                    </div>
                    <div class="rounded-xl bg-[#F8EEEE] p-3">
                        <x-icon name="circle-check" class="size-6 text-[#D7232A]" />
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Rejetées</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['rejetees'] }}</p>
                        <p class="text-sm text-gray-600 mt-1">{{ number_format($stats['montant_rejetees'], 0, ',', ' ') }} F</p>
                    </div>
                    <div class="rounded-xl bg-[#F8EEEE] p-3">
                        <x-icon name="circle-x" class="size-6 text-[#D7232A]" />
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Total Validé</p>
                        <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($stats['montant_validees'], 0, ',', ' ') }} F</p>
                    </div>
                    <div class="rounded-xl bg-[#F8EEEE] p-3">
                        <x-icon name="wallet" class="size-6 text-[#D7232A]" />
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(auth()->user()->role !== 'administrateur')
    <div class="grid lg:grid-cols-2 gap-8">
        <!-- Dernières Dépenses -->
        <div class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">{{ auth()->user()->role === 'directeur' ? 'Factures à décider' : 'Dernières dépenses' }}</h2>
                <a href="{{ route('depenses.index') }}" class="text-[#E31E24] hover:text-[#E31E24] font-semibold text-sm">
                    Voir tout →
                </a>
            </div>

            <div class="space-y-4">
                @forelse($depensesRecentes as $depense)
                <div class="flex flex-col gap-3 rounded-xl border border-gray-100 p-4 transition-colors hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 flex-1 items-center">
                        <div class="w-10 h-10 bg-linear-to-br from-[#E31E24] to-[#C71A20] rounded-full flex items-center justify-center text-white font-bold mr-3 shadow-xs">
                            {{ substr($depense->utilisateur->name, 0, 1) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('depenses.show', $depense) }}" class="mr-2 text-sm font-bold text-gray-900 hover:text-[#C31F28] hover:underline">{{ $depense->libelle }}</a>
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
                    <div class="text-left sm:text-right">
                        <p class="font-black text-gray-900">{{ number_format($depense->prix_total, 0, ',', ' ') }} F</p>
                        <span class="block text-[10px] text-gray-400 font-bold uppercase">{{ $depense->created_at->diffForHumans() }}</span>
                        @if(auth()->user()->role === 'directeur')
                            <a href="{{ route('depenses.show', $depense) }}" class="mt-2 inline-flex min-h-9 items-center gap-1 text-sm font-semibold text-[#C31F28] hover:underline">Examiner <x-icon name="chevron-right" class="size-4" /></a>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">Aucune dépense récente.</p>
                @endforelse
            </div>
        </div>

        <!-- Chantiers Actifs -->
        <div class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-8">
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
                <div class="rounded-xl border border-gray-100 p-4 transition-colors hover:bg-gray-50">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <div class="mr-3 flex size-10 items-center justify-center rounded-lg bg-[#F8EEEE]">
                                <x-icon name="building-2" class="text-[#D7232A]" />
                            </div>
                            <div>
                                <p class="font-bold text-gray-900">{{ $chantier->nom }}</p>
                                <p class="text-xs text-gray-500">{{ $chantier->lieu }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold {{ $textClass }}">
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

    @endif
@endsection
