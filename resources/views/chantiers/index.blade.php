@extends('layouts.admin')

@section('title', 'Gestion des Chantiers - StratAction Consulting')
@section('header', 'Gestion des Chantiers')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Bouton -->
    <div class="flex items-center justify-between">
        <nav class="flex text-sm text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900 font-medium">Chantiers</span>
        </nav>
        @if(auth()->user()->role === 'administrateur')
        <a href="{{ route('chantiers.create') }}" class="px-6 py-3 bg-[#E31E24] text-white font-bold rounded-xl shadow-lg hover:bg-[#C71A20] transition-all flex items-center space-x-2">
            <i class="fa-solid fa-plus"></i>
            <span>Nouveau Chantier</span>
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

    <!-- Tableau des chantiers -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-[#E31E24] to-[#C71A20] text-white">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Nom du Chantier</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Localisation</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Chef de Chantier</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Budget</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Statut</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($chantiers as $chantier)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-[#E31E24] to-[#C71A20] rounded-lg flex items-center justify-center text-white">
                                    <i class="fa-solid fa-building"></i>
                                </div>
                                <span class="ml-3 font-semibold text-gray-900">{{ $chantier->nom }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            <i class="fa-solid fa-location-dot text-[#E31E24] mr-2"></i>{{ $chantier->lieu }}
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $chef = $chantier->chefsChantier->first();
                            @endphp
                            @if($chef)
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-orange-600 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                        {{ substr($chef->name, 0, 1) }}
                                    </div>
                                    <span class="ml-2 text-sm text-gray-900">{{ $chef->name }}</span>
                                </div>
                            @else
                                <span class="text-gray-400 italic">Non assigné</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-semibold text-gray-900">{{ number_format($chantier->budget_alloue, 0, ',', ' ') }} F</span>
                        </td>
                        <td class="px-6 py-4">
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
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 flex items-center justify-end">
                            <a href="{{ route('chantiers.show', $chantier->id) }}" title="Voir détails" class="p-2 text-[#E31E24] hover:bg-blue-50 rounded-lg transition">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            @if(auth()->user()->role === 'administrateur')
                            <a href="{{ route('chantiers.edit', $chantier->id) }}" title="Modifier" class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('chantiers.destroy', $chantier->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Supprimer" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fa-solid fa-inbox text-3xl mb-3"></i>
                            <p>Aucun chantier trouvé.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
