@extends('layouts.admin')

@section('title', 'Modifier Unité - StratAction Consulting')
@section('header', 'Modifier une Unité')

@section('content')
<div class="space-y-6">
    <nav class="flex text-sm text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
        <span class="mx-2">/</span>
        <a href="{{ route('unites.index') }}" class="hover:text-[#E31E24]">Unités</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium">Modifier</span>
    </nav>

    <div class="bg-white rounded-2xl shadow-lg p-8 max-w-2xl">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Modifier l'Unité</h1>

        <form action="{{ route('unites.update', $unite->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <i class="fa-solid fa-scale-balanced text-green-500 mr-2"></i>Nom *
                </label>
                <input type="text" name="nom" value="{{ old('nom', $unite->nom) }}" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none {{ $errors->has('nom') ? 'border-red-500' : '' }}" placeholder="Ex: Kilogramme, Litre, Mètre">
                @error('nom')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <i class="fa-solid fa-code text-[#E31E24] mr-2"></i>Symbole *
                </label>
                <input type="text" name="symbole" value="{{ old('symbole', $unite->symbole) }}" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none {{ $errors->has('symbole') ? 'border-red-500' : '' }}" placeholder="Ex: kg, L, m">
                @error('symbole')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <i class="fa-solid fa-pen text-purple-500 mr-2"></i>Description
                </label>
                <textarea name="description" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" rows="4" placeholder="Décrivez cette unité...">{{ old('description', $unite->description) }}</textarea>
            </div>

            <div class="flex items-center space-x-3">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $unite->is_active) ? 'checked' : '' }} class="w-4 h-4 text-[#E31E24] border-gray-300 rounded focus:ring-blue-500">
                <label for="is_active" class="text-gray-700 font-medium">Actif</label>
            </div>

            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <button type="submit" class="flex-1 px-6 py-3 bg-[#E31E24] text-white font-bold rounded-xl hover:bg-[#C71A20] transition-all flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-save"></i>
                    <span>Mettre à Jour</span>
                </button>
                <a href="{{ route('unites.index') }}" class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-300 transition-all flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-times"></i>
                    <span>Annuler</span>
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
