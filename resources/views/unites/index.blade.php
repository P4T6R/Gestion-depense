@extends('layouts.admin')

@section('title', 'Gestion des Unités - StratAction Consulting')
@section('header', 'Gestion des Unités de Mesure')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Bouton -->
    <div class="flex items-center justify-between">
        <nav class="flex text-sm text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900 font-medium">Unités de Mesure</span>
        </nav>
        <a href="{{ route('unites.create') }}" class="px-6 py-3 bg-[#E31E24] text-white font-bold rounded-xl shadow-lg hover:bg-[#C71A20] transition-all flex items-center space-x-2">
            <i class="fa-solid fa-plus"></i>
            <span>Nouvelle Unité</span>
        </a>
    </div>

    <!-- Messages -->
    @if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-xl">
        <div class="flex">
            <i class="fa-solid fa-check-circle text-green-500 mt-0.5"></i>
            <p class="ml-3 text-sm text-green-700">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-xl">
        <div class="flex">
            <i class="fa-solid fa-exclamation-circle text-red-500 mt-0.5"></i>
            <p class="ml-3 text-sm text-red-700">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    <!-- Tableau -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-[#E31E24] to-[#C71A20] text-white">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Nom</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Symbole</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Description</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold">Dépenses</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Statut</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($unites as $unite)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center text-white">
                                    <i class="fa-solid fa-scale-balanced"></i>
                                </div>
                                <span class="ml-3 font-semibold text-gray-900">{{ $unite->nom }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600 font-mono text-sm font-bold">{{ $unite->symbole }}</td>
                        <td class="px-6 py-4 text-gray-600 text-sm">{{ Str::limit($unite->description, 50) ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">{{ $unite->depenses_count ?? 0 }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($unite->is_active)
                                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">Active</span>
                            @else
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-semibold">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 flex items-center justify-end">
                            <a href="{{ route('unites.show', $unite->id) }}" title="Voir" class="p-2 text-[#E31E24] hover:bg-blue-50 rounded-lg transition">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('unites.edit', $unite->id) }}" title="Modifier" class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('unites.toggle-active', $unite->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" title="{{ $unite->is_active ? 'Désactiver' : 'Activer' }}" class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg transition">
                                    <i class="fa-solid fa-toggle-{{ $unite->is_active ? 'on' : 'off' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('unites.destroy', $unite->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Supprimer" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fa-solid fa-inbox text-3xl mb-3"></i>
                            <p>Aucune unité trouvée.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center">
        {{ $unites->links() }}
    </div>
</div>
@endsection
