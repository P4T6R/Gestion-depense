@extends('layouts.admin')

@section('title', 'Détails Catégorie - StratAction Consulting')
@section('header', 'Détails de la Catégorie')

@section('content')
<div class="space-y-6">
    <nav class="flex text-sm text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
        <span class="mx-2">/</span>
        <a href="{{ route('categories.index') }}" class="hover:text-[#E31E24]">Catégories</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium">{{ $categorie->nom }}</span>
    </nav>

    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $categorie->nom }}</h1>
            <p class="text-gray-600 mt-2">Code: <span class="font-mono font-semibold">{{ $categorie->code }}</span></p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('categories.edit', $categorie->id) }}" class="px-6 py-3 bg-orange-600 text-white font-bold rounded-xl shadow-lg hover:bg-orange-700 transition-all flex items-center space-x-2">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Modifier</span>
            </a>
            <form action="{{ route('categories.destroy', $categorie->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-6 py-3 bg-red-600 text-white font-bold rounded-xl shadow-lg hover:bg-red-700 transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-trash"></i>
                    <span>Supprimer</span>
                </button>
            </form>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Informations</h2>
                <div class="space-y-4">
                    <div>
                        <p class="text-gray-600 text-sm">Nom</p>
                        <p class="text-gray-900 font-semibold text-lg">{{ $categorie->nom }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Code</p>
                        <p class="text-gray-900 font-semibold font-mono">{{ $categorie->code }}</p>
                    </div>
                    @if($categorie->description)
                    <div>
                        <p class="text-gray-600 text-sm">Description</p>
                        <p class="text-gray-900 mt-2">{{ $categorie->description }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Statut</h2>
                @if($categorie->is_active)
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold">Active</span>
                    </div>
                @else
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-semibold">Inactive</span>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">
                    <i class="fa-solid fa-receipt text-[#E31E24] mr-2"></i>Dépenses
                </h2>
                <p class="text-3xl font-bold text-[#E31E24]">{{ $categorie->depenses_count ?? 0 }}</p>
                <p class="text-gray-600 text-sm mt-2">dépenses associées</p>
            </div>
        </div>
    </div>
</div>
@endsection
