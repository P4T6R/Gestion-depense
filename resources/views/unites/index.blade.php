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
            <x-icon name="plus" class="" />
            <span>Nouvelle Unité</span>
        </a>
    </div>

    <!-- Messages -->
    @if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-xl">
        <div class="flex">
            <x-icon name="circle-check" class="text-green-500 mt-0.5" />
            <p class="ml-3 text-sm text-green-700">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-xl">
        <div class="flex">
            <x-icon name="circle-alert" class="text-red-500 mt-0.5" />
            <p class="ml-3 text-sm text-red-700">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    <!-- Tableau -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-[#FAF9F7] text-[#444]">
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
                                <div class="w-10 h-10 bg-[#F8EEEE] rounded-lg flex items-center justify-center text-[#C31F28]">
                                    <x-icon name="scale" class="" />
                                </div>
                                <span class="ml-3 font-semibold text-gray-900">{{ $unite->nom }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600 font-mono text-sm font-bold">{{ $unite->symbole }}</td>
                        <td class="px-6 py-4 text-gray-600 text-sm">{{ Str::limit($unite->description, 50) ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 bg-[#F5F4F2] text-[#444] rounded-full text-xs font-semibold">{{ $unite->depenses_count ?? 0 }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($unite->is_active)
                                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">Active</span>
                            @else
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-semibold">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 flex items-center justify-end">
                            <a href="{{ route('unites.show', $unite->id) }}" title="Voir" class="p-2 text-[#555] hover:bg-[#F8EEEE] hover:text-[#C31F28] rounded-lg transition">
                                <x-icon name="eye" class="" />
                            </a>
                            <a href="{{ route('unites.edit', $unite->id) }}" title="Modifier" class="p-2 text-[#555] hover:bg-[#F8EEEE] hover:text-[#C31F28] rounded-lg transition">
                                <x-icon name="square-pen" class="" />
                            </a>
                            <form action="{{ route('unites.toggle-active', $unite->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" title="{{ $unite->is_active ? 'Désactiver' : 'Activer' }}" class="p-2 text-[#555] hover:bg-[#F8EEEE] hover:text-[#C31F28] rounded-lg transition">
                                    <x-icon :name="$unite->is_active ? 'toggle-right' : 'toggle-left'" />
                                </button>
                            </form>
                            <form action="{{ route('unites.destroy', $unite->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Supprimer" class="p-2 text-[#555] hover:bg-[#F8EEEE] hover:text-[#C31F28] rounded-lg transition">
                                    <x-icon name="trash-2" class="" />
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <x-icon name="inbox" class="size-8 mb-3" />
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
