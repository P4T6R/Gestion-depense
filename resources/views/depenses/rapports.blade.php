@extends('layouts.admin')

@section('title', 'Rapports - StratAction Consulting')
@section('header', 'Rapports et Statistiques')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex text-sm text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium">Rapports</span>
    </nav>

    <!-- Filtres -->
    <div class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <x-icon name="list-filter" class="text-[#D7232A] mr-3" />
            Filtres
        </h2>
        
        <form method="GET" action="{{ route('depenses.rapports') }}" class="space-y-6">
            <div class="grid md:grid-cols-5 gap-4">
                <!-- Date début -->
                <div>
                    <label for="rapport-date-debut" class="block text-sm font-semibold text-gray-700 mb-2">Date début</label>
                    <input type="date" id="rapport-date-debut" name="date_debut" value="{{ $filters['date_debut'] ?? '' }}" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#D7232A] outline-hidden">
                </div>

                <!-- Date fin -->
                <div>
                    <label for="rapport-date-fin" class="block text-sm font-semibold text-gray-700 mb-2">Date fin</label>
                    <input type="date" id="rapport-date-fin" name="date_fin" value="{{ $filters['date_fin'] ?? '' }}" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#D7232A] outline-hidden">
                </div>

                <!-- Chantier -->
                <div>
                    <label for="rapport-chantier" class="block text-sm font-semibold text-gray-700 mb-2">Chantier</label>
                    <select id="rapport-chantier" name="chantier_id" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#D7232A] outline-hidden">
                        <option value="">Tous</option>
                        @foreach($chantiers as $chantier)
                            <option value="{{ $chantier->id }}" {{ ($filters['chantier_id'] ?? '') == $chantier->id ? 'selected' : '' }}>{{ $chantier->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Catégorie -->
                <div>
                    <label for="rapport-categorie" class="block text-sm font-semibold text-gray-700 mb-2">Catégorie</label>
                    <select id="rapport-categorie" name="categorie_id" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#D7232A] outline-hidden">
                        <option value="">Toutes</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ ($filters['categorie_id'] ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Statut -->
                <div>
                    <label for="rapport-statut" class="block text-sm font-semibold text-gray-700 mb-2">Statut</label>
                    <select id="rapport-statut" name="statut_validation" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#D7232A] outline-hidden">
                        <option value="">Tous</option>
                        <option value="en_attente" {{ ($filters['statut_validation'] ?? '') == 'en_attente' ? 'selected' : '' }}>En attente</option>
                        <option value="acceptee" {{ ($filters['statut_validation'] ?? '') == 'acceptee' ? 'selected' : '' }}>Acceptée</option>
                        <option value="validee" {{ ($filters['statut_validation'] ?? '') == 'validee' ? 'selected' : '' }}>Validée</option>
                        <option value="rejetee" {{ ($filters['statut_validation'] ?? '') == 'rejetee' ? 'selected' : '' }}>Rejetée</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 bg-[#E31E24] text-white font-semibold rounded-lg hover:bg-[#C71A20] transition">
                    <x-icon name="search" class="mr-2" />Filtrer
                </button>
                <a href="{{ route('depenses.rapports') }}" class="px-6 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition">
                    <x-icon name="x" class="mr-2" />Réinitialiser
                </a>
            </div>
        </form>
    </div>

    <!-- Statistiques Globales -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-[#E7E5E1] bg-white p-6 text-[#232323]">
            <div class="flex items-center justify-between mb-2">
                <x-icon name="file-text" class="size-7 text-[#D7232A]" />
                <span class="text-sm font-medium opacity-90">Total</span>
            </div>
            <p class="text-3xl font-bold">{{ $stats['total_depenses'] }}</p>
            <p class="text-sm opacity-80 mt-1">Dépenses</p>
        </div>

        <div class="rounded-2xl border border-[#E7E5E1] bg-white p-6 text-[#232323]">
            <div class="flex items-center justify-between mb-2">
                <x-icon name="coins" class="size-7 text-[#D7232A]" />
                <span class="text-sm font-medium opacity-90">Montant Total</span>
            </div>
            <p class="text-3xl font-bold">{{ number_format($stats['montant_total'], 0, ',', ' ') }} F</p>
            <p class="text-sm opacity-80 mt-1">Toutes dépenses</p>
        </div>

        <div class="rounded-2xl border border-[#E7E5E1] bg-white p-6 text-[#232323]">
            <div class="flex items-center justify-between mb-2">
                <x-icon name="circle-check" class="size-7 text-[#D7232A]" />
                <span class="text-sm font-medium opacity-90">Validées</span>
            </div>
            <p class="text-3xl font-bold">{{ $stats['validees'] }}</p>
            <p class="text-sm opacity-80 mt-1">{{ number_format($stats['montant_valide'], 0, ',', ' ') }} F</p>
        </div>

        <div class="rounded-2xl border border-[#E7E5E1] bg-white p-6 text-[#232323]">
            <div class="flex items-center justify-between mb-2">
                <x-icon name="hourglass" class="size-7 text-[#D7232A]" />
                <span class="text-sm font-medium opacity-90">En Attente</span>
            </div>
            <p class="text-3xl font-bold">{{ $stats['en_attente'] }}</p>
            <p class="text-sm opacity-80 mt-1">À traiter</p>
        </div>
    </div>

    <!-- Boutons d'export -->
    <div class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
            <x-icon name="download" class="text-gray-500 mr-3" />
            Exporter les données
        </h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('depenses.export.pdf', request()->query()) }}" class="px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 transition flex items-center">
                <x-icon name="file-text" class="mr-2" />PDF
            </a>
            <a href="{{ route('depenses.export.excel', request()->query()) }}" class="px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 transition flex items-center">
                <x-icon name="file-spreadsheet" class="mr-2" />Excel
            </a>
            <a href="{{ route('depenses.export.csv', request()->query()) }}" class="px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 transition flex items-center">
                <x-icon name="file-spreadsheet" class="mr-2" />CSV
            </a>
        </div>
    </div>

    <!-- Répartition par Catégorie -->
    <div class="grid md:grid-cols-2 gap-6">
        <div class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <x-icon name="chart-pie" class="text-[#D7232A] mr-3" />
                Par Catégorie
            </h2>
            <div class="space-y-4">
                @forelse($parCategorie as $cat)
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900">{{ $cat['nom'] }}</p>
                        <p class="text-sm text-gray-500">{{ $cat['count'] }} dépenses</p>
                    </div>
                    <p class="text-lg font-bold text-[#C31F28]">{{ number_format($cat['montant'], 0, ',', ' ') }} F</p>
                </div>
                @empty
                <p class="text-gray-500 text-center py-8">Aucune donnée</p>
                @endforelse
            </div>
        </div>

        <!-- Répartition par Chantier -->
        <div class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <x-icon name="building-2" class="text-[#D7232A] mr-3" />
                Par Chantier
            </h2>
            <div class="space-y-4">
                @forelse($parChantier as $ch)
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900">{{ $ch['nom'] }}</p>
                        <p class="text-sm text-gray-500">{{ $ch['count'] }} dépenses</p>
                    </div>
                    <p class="text-lg font-bold text-[#C31F28]">{{ number_format($ch['montant'], 0, ',', ' ') }} F</p>
                </div>
                @empty
                <p class="text-gray-500 text-center py-8">Aucune donnée</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Liste des dépenses -->
    <div class="overflow-hidden rounded-2xl border border-[#E7E5E1] bg-white">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900 flex items-center">
                <x-icon name="list" class="text-[#D7232A] mr-3" />
                Détails des Dépenses ({{ $depenses->count() }})
            </h2>
        </div>
        
        <p class="px-6 pt-3 text-xs text-gray-500 sm:hidden">Faites défiler le tableau pour voir toutes les colonnes.</p>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase">Chantier</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase">Dépense</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase">Catégorie</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase">Montant</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($depenses as $depense)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#{{ $depense->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $depense->chantier->nom ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900"><a href="{{ route('depenses.show', $depense) }}" class="font-medium hover:text-[#C31F28] hover:underline">{{ $depense->libelle }}</a></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $depense->categorie->nom ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-[#E31E24]">{{ number_format($depense->prix_total, 0, ',', ' ') }} F</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $colors = [
                                    'en_attente' => 'yellow',
                                    'acceptee' => 'blue',
                                    'validee' => 'green',
                                    'rejetee' => 'red'
                                ];
                                $color = $colors[$depense->statut_validation] ?? 'gray';
                            @endphp
                            <span class="px-3 py-1 text-xs font-bold rounded-full bg-{{ $color }}-100 text-{{ $color }}-700">
                                {{ ucfirst(str_replace('_', ' ', $depense->statut_validation)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $depense->created_at->format('d/m/Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <x-icon name="inbox" class="size-10 mb-3 text-gray-300" />
                            <p>Aucune dépense trouvée avec ces filtres</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
