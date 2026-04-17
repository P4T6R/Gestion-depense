@extends('layouts.admin')

@section('title', 'Gestion des Dépenses - StratAction Consulting')
@section('header', 'Gestion des Dépenses')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Bouton -->
    <div class="flex items-center justify-between">
        <nav class="flex text-sm text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900 font-medium">Dépenses</span>
        </nav>
        @if(in_array(auth()->user()->role, ['chef_chantier', 'directeur']))
        <a href="{{ route('depenses.create') }}" class="px-6 py-3 bg-gradient-to-r from-[#E31E24] to-[#C71A20] text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center gap-3">
            <i class="fa-solid fa-plus"></i>
            <span>Nouvelle Dépense</span>
        </a>
        @endif
    </div>

    <!-- Messages de succès -->
    @if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-xl">
        <div class="flex">
            <i class="fa-solid fa-check-circle text-green-500 mt-0.5"></i>
            <p class="ml-3 text-sm text-green-700">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    <!-- Statistiques rapides -->
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        @php
            $stats = [
                'en_attente' => $depenses->where('statut_validation', 'en_attente')->count(),
                'montant_en_attente' => $depenses->where('statut_validation', 'en_attente')->sum('prix_total'),
                'acceptee' => $depenses->where('statut_validation', 'acceptee')->count(),
                'montant_acceptee' => $depenses->where('statut_validation', 'acceptee')->sum('prix_total'),
                'validee' => $depenses->where('statut_validation', 'validee')->count(),
                'montant_validee' => $depenses->where('statut_validation', 'validee')->sum('prix_total'),
                'rejetee' => $depenses->where('statut_validation', 'rejetee')->count(),
                'montant_rejetee' => $depenses->where('statut_validation', 'rejetee')->sum('prix_total'),
            ];
        @endphp
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-2xl shadow-md hover:shadow-xl p-6 border-l-4 border-yellow-500 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-xs font-bold uppercase tracking-wide">En Attente</p>
                    <p class="text-3xl font-extrabold text-yellow-700 mt-2">{{ $stats['en_attente'] }}</p>
                    <p class="text-xs text-gray-600 mt-2 font-medium">{{ number_format($stats['montant_en_attente'], 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="w-16 h-16 bg-yellow-500 bg-opacity-20 rounded-2xl flex items-center justify-center">
                    <i class="fa-solid fa-hourglass-half text-3xl text-yellow-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl shadow-md hover:shadow-xl p-6 border-l-4 border-[#E31E24] card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-xs font-bold uppercase tracking-wide">Acceptées</p>
                    <p class="text-3xl font-extrabold text-blue-700 mt-2">{{ $stats['acceptee'] }}</p>
                    <p class="text-xs text-gray-600 mt-2 font-medium">{{ number_format($stats['montant_acceptee'], 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="w-16 h-16 bg-[#E31E24] bg-opacity-20 rounded-2xl flex items-center justify-center">
                    <i class="fa-solid fa-thumbs-up text-3xl text-[#E31E24]"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl shadow-md hover:shadow-xl p-6 border-l-4 border-green-500 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-xs font-bold uppercase tracking-wide">Validées</p>
                    <p class="text-3xl font-extrabold text-green-700 mt-2">{{ $stats['validee'] }}</p>
                    <p class="text-xs text-gray-600 mt-2 font-medium">{{ number_format($stats['montant_validee'], 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="w-16 h-16 bg-green-500 bg-opacity-20 rounded-2xl flex items-center justify-center">
                    <i class="fa-solid fa-check-circle text-3xl text-green-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-2xl shadow-md hover:shadow-xl p-6 border-l-4 border-red-500 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-xs font-bold uppercase tracking-wide">Rejetées</p>
                    <p class="text-3xl font-extrabold text-red-700 mt-2">{{ $stats['rejetee'] }}</p>
                    <p class="text-xs text-gray-600 mt-2 font-medium">{{ number_format($stats['montant_rejetee'], 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="w-16 h-16 bg-red-500 bg-opacity-20 rounded-2xl flex items-center justify-center">
                    <i class="fa-solid fa-ban text-3xl text-red-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des dépenses -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 animate-slide-in">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-[#E31E24] to-[#C71A20] text-white">
                    <tr>
                        <th class="px-8 py-5 text-left text-xs font-bold uppercase tracking-wider">Produit</th>
                        <th class="px-8 py-5 text-left text-xs font-bold uppercase tracking-wider">Chantier</th>
                        <th class="px-8 py-5 text-left text-xs font-bold uppercase tracking-wider">Créé par</th>
                        <th class="px-8 py-5 text-right text-xs font-bold uppercase tracking-wider">Montant</th>
                        <th class="px-8 py-5 text-left text-xs font-bold uppercase tracking-wider">Statut</th>
                        <th class="px-8 py-5 text-right text-xs font-bold uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($depenses as $depense)
                    <tr class="group transition-all duration-200">
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-[#E31E24] to-[#C71A20] rounded-xl flex items-center justify-center text-white shadow-sm group-hover:shadow-md transition-shadow">
                                    <i class="fa-solid fa-box text-lg"></i>
                                </div>
                                <div>
                                    <span class="font-bold text-gray-900 text-sm block">{{ $depense->nom_produit }}</span>
                                    <span class="text-xs text-gray-500">{{ $depense->categorie->nom ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-5">
                            <span class="text-sm text-gray-700 font-medium">{{ $depense->chantier->nom }}</span>
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-xs font-bold text-gray-600">
                                    {{ substr($depense->utilisateur->name, 0, 1) }}
                                </div>
                                <span class="text-sm text-gray-700">{{ $depense->utilisateur->name }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-5 text-right">
                            <span class="text-base font-bold text-gray-900">{{ number_format($depense->prix_total, 0, ',', ' ') }}</span>
                            <span class="text-xs text-gray-500 ml-1">FCFA</span>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusColors = [
                                    'en_attente' => 'bg-yellow-100 text-yellow-700',
                                    'acceptee' => 'bg-blue-100 text-blue-700',
                                    'validee' => 'bg-green-100 text-green-700',
                                    'rejetee' => 'bg-red-100 text-red-700',
                                ];
                                $statusLabels = [
                                    'en_attente' => 'En attente',
                                    'acceptee' => 'Acceptée',
                                    'validee' => 'Validée',
                                    'rejetee' => 'Rejetée',
                                ];
                                $statusClass = $statusColors[$depense->statut_validation] ?? 'bg-gray-100 text-gray-700';
                            @endphp
                            <span class="px-4 py-2 rounded-lg text-xs font-bold {{ $statusClass }} inline-flex items-center gap-2 shadow-sm">
                                @if($depense->statut_validation === 'en_attente')
                                    <i class="fa-solid fa-clock"></i>
                                @elseif($depense->statut_validation === 'acceptee')
                                    <i class="fa-solid fa-thumbs-up"></i>
                                @elseif($depense->statut_validation === 'validee')
                                    <i class="fa-solid fa-circle-check"></i>
                                @else
                                    <i class="fa-solid fa-ban"></i>
                                @endif
                                {{ $statusLabels[$depense->statut_validation] ?? 'Inconnu' }}
                            </span>
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('depenses.show', $depense->id) }}" title="Voir détails" class="p-2.5 text-gray-600 hover:text-[#E31E24] hover:bg-red-50 rounded-lg transition-all duration-200">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                @if(in_array(auth()->user()->role, ['chef_chantier', 'directeur']) && $depense->user_id === auth()->id() && $depense->statut_validation === 'en_attente')
                                <a href="{{ route('depenses.edit', $depense->id) }}" title="Modifier" class="p-2.5 text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-all duration-200">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('depenses.destroy', $depense->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette dépense ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Supprimer" class="p-2.5 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all duration-200">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-8 py-16 text-center">
                            <div class="flex flex-col items-center justify-center gap-4 animate-fade-in">
                                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-inbox text-4xl text-gray-400"></i>
                                </div>
                                <div>
                                    <p class="text-lg font-semibold text-gray-700">Aucune dépense trouvée</p>
                                    <p class="text-sm text-gray-500 mt-1">Créez votre première dépense pour commencer</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center">
        {{ $depenses->links() }}
    </div>
</div>
@endsection
