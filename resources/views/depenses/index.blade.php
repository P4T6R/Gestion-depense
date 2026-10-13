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
        <a href="{{ route('depenses.create') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-[#D7232A] px-5 py-2.5 font-semibold text-white transition-colors hover:bg-[#BA1E26]">
            <x-icon name="plus" class="" />
            <span>Nouvelle Dépense</span>
        </a>
        @endif
    </div>

    <!-- Messages de succès -->
    @if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-xl">
        <div class="flex">
            <x-icon name="circle-check" class="text-green-500 mt-0.5" />
            <p class="ml-3 text-sm text-green-700">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    <!-- Statistiques rapides -->
    <div class="grid md:grid-cols-4 gap-4 mb-6">

        <div class="rounded-xl border border-[#E7E5E1] bg-white p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-xs font-bold uppercase tracking-wide">En Attente</p>
                    <p class="text-3xl font-extrabold text-[#232323] mt-2">{{ $stats['en_attente'] }}</p>
                    <p class="text-xs text-gray-600 mt-2 font-medium">{{ number_format($stats['montant_en_attente'], 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="flex size-9 items-center justify-center rounded-lg bg-[#F7F6F3]">
                    <x-icon name="hourglass" class="size-5 text-[#555]" />
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-[#E7E5E1] bg-white p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-xs font-bold uppercase tracking-wide">Acceptées</p>
                    <p class="text-3xl font-extrabold text-[#232323] mt-2">{{ $stats['acceptee'] }}</p>
                    <p class="text-xs text-gray-600 mt-2 font-medium">{{ number_format($stats['montant_acceptee'], 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="flex size-9 items-center justify-center rounded-lg bg-[#F7F6F3]">
                    <x-icon name="thumbs-up" class="size-5 text-[#555]" />
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-[#E7E5E1] bg-white p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-xs font-bold uppercase tracking-wide">Validées</p>
                    <p class="text-3xl font-extrabold text-[#232323] mt-2">{{ $stats['validee'] }}</p>
                    <p class="text-xs text-gray-600 mt-2 font-medium">{{ number_format($stats['montant_validee'], 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="flex size-9 items-center justify-center rounded-lg bg-[#F7F6F3]">
                    <x-icon name="circle-check" class="size-5 text-[#555]" />
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-[#E7E5E1] bg-white p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-xs font-bold uppercase tracking-wide">Rejetées</p>
                    <p class="text-3xl font-extrabold text-[#232323] mt-2">{{ $stats['rejetee'] }}</p>
                    <p class="text-xs text-gray-600 mt-2 font-medium">{{ number_format($stats['montant_rejetee'], 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="flex size-9 items-center justify-center rounded-lg bg-[#F7F6F3]">
                    <x-icon name="ban" class="size-5 text-[#555]" />
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des dépenses -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 animate-slide-in">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-[#FAF9F7] text-[#444]">
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
                                <div class="w-12 h-12 bg-[#F8EEEE] rounded-xl flex items-center justify-center text-[#C31F28] shadow-xs group-hover:shadow-md transition-shadow">
                                    <x-icon name="package" class="size-5" />
                                </div>
                                <div>
                                    <span class="font-bold text-gray-900 text-sm block">{{ $depense->libelle }}</span>
                                    <span class="text-xs text-gray-500">{{ $depense->categorie->nom ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-5">
                            <span class="text-sm text-gray-700 font-medium">{{ $depense->chantier?->nom ?? 'Chantier archivé' }}</span>
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-xs font-bold text-gray-600">
                                    {{ substr(($depense->utilisateur?->name ?? 'Utilisateur supprimé'), 0, 1) }}
                                </div>
                                <span class="text-sm text-gray-700">{{ ($depense->utilisateur?->name ?? 'Utilisateur supprimé') }}</span>
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
                            <span class="px-4 py-2 rounded-lg text-xs font-bold {{ $statusClass }} inline-flex items-center gap-2 shadow-xs">
                                @if($depense->statut_validation === 'en_attente')
                                    <x-icon name="clock-3" class="" />
                                @elseif($depense->statut_validation === 'acceptee')
                                    <x-icon name="thumbs-up" class="" />
                                @elseif($depense->statut_validation === 'validee')
                                    <x-icon name="circle-check" class="" />
                                @else
                                    <x-icon name="ban" class="" />
                                @endif
                                {{ $statusLabels[$depense->statut_validation] ?? 'Inconnu' }}
                            </span>
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('depenses.show', $depense->id) }}" title="Voir détails" class="p-2.5 text-[#555] hover:text-[#C31F28] hover:bg-[#F8EEEE] rounded-lg transition-all duration-200">
                                    <x-icon name="eye" class="" />
                                </a>
                                @can('update', $depense)
                                <a href="{{ route('depenses.edit', $depense->id) }}" title="{{ $depense->statut_validation === 'rejetee' ? 'Corriger et soumettre à nouveau' : 'Modifier' }}" aria-label="{{ $depense->statut_validation === 'rejetee' ? 'Corriger et soumettre à nouveau' : 'Modifier' }} {{ $depense->libelle }}" class="p-2.5 text-[#555] hover:text-[#C31F28] hover:bg-[#F8EEEE] rounded-lg transition-all duration-200">
                                    <x-icon name="square-pen" class="" />
                                </a>
                                @endcan
                                @can('delete', $depense)
                                <form action="{{ route('depenses.destroy', $depense->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette dépense ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Supprimer" class="p-2.5 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all duration-200">
                                        <x-icon name="trash-2" class="" />
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-8 py-16 text-center">
                            <div class="flex flex-col items-center justify-center gap-4 animate-fade-in">
                                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center">
                                    <x-icon name="inbox" class="size-10 text-gray-400" />
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
