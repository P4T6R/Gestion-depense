@extends('layouts.admin')

@section('title', 'Créer Catégorie - StratAction Consulting')
@section('header', 'Créer une Catégorie')

@section('content')
<div class="space-y-6">
    <nav class="flex text-sm text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
        <span class="mx-2">/</span>
        <a href="{{ route('categories.index') }}" class="hover:text-[#E31E24]">Catégories</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium">Créer</span>
    </nav>

    <div class="bg-white rounded-2xl shadow-lg p-8 max-w-2xl">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Nouvelle Catégorie</h1>

        <form action="{{ route('categories.store') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <i class="fa-solid fa-tag text-purple-500 mr-2"></i>Nom *
                </label>
                <input type="text" name="nom" value="{{ old('nom') }}" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none {{ $errors->has('nom') ? 'border-red-500' : '' }}" placeholder="Ex: Matériaux de construction">
                @error('nom')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <i class="fa-solid fa-barcode text-[#E31E24] mr-2"></i>Code *
                </label>
                <input type="text" name="code" value="{{ old('code') }}" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none {{ $errors->has('code') ? 'border-red-500' : '' }}" placeholder="Ex: MAT-001">
                @error('code')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <i class="fa-solid fa-pen text-purple-500 mr-2"></i>Description
                </label>
                <textarea name="description" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" rows="4" placeholder="Décrivez cette catégorie...">{{ old('description') }}</textarea>
            </div>

            <div class="flex items-center space-x-3">
                <input type="checkbox" name="is_active" id="is_active" value="1" checked class="w-4 h-4 text-[#E31E24] border-gray-300 rounded focus:ring-blue-500">
                <label for="is_active" class="text-gray-700 font-medium">Actif</label>
            </div>

            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <button type="submit" class="flex-1 px-6 py-3 bg-[#E31E24] text-white font-bold rounded-xl hover:bg-[#C71A20] transition-all flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-save"></i>
                    <span>Créer</span>
                </button>
                <a href="{{ route('categories.index') }}" class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-300 transition-all flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-times"></i>
                    <span>Annuler</span>
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
