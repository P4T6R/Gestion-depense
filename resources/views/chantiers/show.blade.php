@extends('layouts.admin')

@section('title', 'Détails Chantier - StratAction Consulting')
@section('header', 'Détails du Chantier')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex text-sm text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
        <span class="mx-2">/</span>
        <a href="{{ route('chantiers.index') }}" class="hover:text-[#E31E24]">Chantiers</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium">{{ $chantier->nom }}</span>
    </nav>

    <!-- En-tête avec actions -->
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $chantier->nom }}</h1>
            <p class="text-gray-600 mt-2">
                <i class="fa-solid fa-location-dot text-[#E31E24] mr-2"></i>{{ $chantier->lieu }}
            </p>
        </div>
        @if(auth()->user()->role === 'administrateur')
        <div class="flex space-x-2">
            <a href="{{ route('chantiers.edit', $chantier->id) }}" class="px-6 py-3 bg-orange-600 text-white font-bold rounded-xl shadow-lg hover:bg-orange-700 transition-all flex items-center space-x-2">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Modifier</span>
            </a>
            <form action="{{ route('chantiers.destroy', $chantier->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-6 py-3 bg-red-600 text-white font-bold rounded-xl shadow-lg hover:bg-red-700 transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-trash"></i>
                    <span>Supprimer</span>
                </button>
            </form>
        </div>
        @endif
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        <!-- Informations principales -->
        <div class="md:col-span-2 space-y-6">
            <!-- Card - Informations Générales -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fa-solid fa-info-circle text-[#E31E24] mr-3"></i>
                    Informations Générales
                </h2>
                <div class="space-y-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="border-b border-gray-200 pb-4">
                            <p class="text-gray-600 text-sm">Date de Début</p>
                            <p class="text-gray-900 font-semibold">{{ $chantier->date_debut->format('d/m/Y') }}</p>
                        </div>
                        <div class="border-b border-gray-200 pb-4">
                            <p class="text-gray-600 text-sm">Statut</p>
                            @php
                                $statusColors = [
                                    'actif' => 'bg-green-100 text-green-700',
                                    'suspendu' => 'bg-yellow-100 text-yellow-700',
                                    'termine' => 'bg-gray-100 text-gray-700',
                                ];
                                $statusClass = $statusColors[$chantier->statut] ?? 'bg-gray-100 text-gray-700';
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">
                                {{ ucfirst($chantier->statut) }}
                            </span>
                        </div>
                    </div>
                    @if($chantier->description)
                    <div class="pt-4 border-t border-gray-200">
                        <p class="text-gray-600 text-sm">Description</p>
                        <p class="text-gray-900 mt-2">{{ $chantier->description }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Card - Budget -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fa-solid fa-money-bill-wave text-green-500 mr-3"></i>
                    Budget & Dépenses
                </h2>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-gray-600 text-sm">Budget Alloué</p>
                            <p class="text-gray-900 font-semibold">{{ number_format($chantier->budget_alloue, 0, ',', ' ') }} F</p>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-gray-600 text-sm">Budget Consommé</p>
                            <p class="text-gray-900 font-semibold">{{ number_format($chantier->budget_consomme, 0, ',', ' ') }} F</p>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-gray-200">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-gray-600 text-sm">Budget Restant</p>
                            <p class="text-gray-900 font-semibold">{{ number_format($chantier->budget_alloue - $chantier->budget_consomme, 0, ',', ' ') }} F</p>
                        </div>
                        @php
                            $percentageUsed = $chantier->budget_alloue > 0 ? ($chantier->budget_consomme / $chantier->budget_alloue) * 100 : 0;
                        @endphp
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-green-500 to-blue-500 h-2 rounded-full" style="width: {{ min($percentageUsed, 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">{{ number_format($percentageUsed, 1) }}% consommé</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar - Chef de Chantier -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fa-solid fa-user-tie text-orange-500 mr-3"></i>
                    Chef de Chantier
                </h2>
                @php
                    $chef = $chantier->chefsChantier->first();
                @endphp
                @if($chef)
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                        {{ substr($chef->name, 0, 1) }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">{{ $chef->name }}</p>
                        <p class="text-sm text-gray-600">{{ $chef->email }}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fa-solid fa-phone mr-1"></i>{{ $chef->phone ?? 'N/A' }}
                        </p>
                    </div>
                </div>
                @else
                <p class="text-gray-500 italic">Non assigné</p>
                @endif
            </div>

            <!-- Statistiques rapides -->
            <div class="bg-gradient-to-br from-[#E31E24] to-[#C71A20] rounded-2xl shadow-lg p-8 text-white">
                <h2 class="text-xl font-bold mb-6 flex items-center">
                    <i class="fa-solid fa-chart-bar mr-3"></i>
                    Dépenses
                </h2>
                <div class="space-y-4">
                    <div class="bg-white/20 rounded-xl p-4">
                        <p class="text-blue-100 text-sm">Total des Dépenses</p>
                        <p class="text-2xl font-bold">{{ $chantier->depenses_count ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des dépenses récentes -->
    @if($chantier->depenses->count() > 0)
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="px-8 py-6 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">Dernières Dépenses</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Description</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Montant</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($chantier->depenses->take(5) as $depense)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-gray-900">{{ $depense->libelle ?? 'N/A' }}</td>
                        <td class="px-6 py-4 font-semibold text-gray-900">{{ number_format($depense->prix_total, 0, ',', ' ') }} F</td>
                        <td class="px-6 py-4 text-gray-600 text-sm">{{ $depense->created_at->format('d/m/Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
