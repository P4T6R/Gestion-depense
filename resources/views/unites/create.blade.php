@extends('layouts.admin')

@section('title', 'Créer Unité - StratAction Consulting')
@section('header', 'Créer une Unité')

@section('content')
<div class="space-y-6">
    <nav class="flex text-sm text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
        <span class="mx-2">/</span>
        <a href="{{ route('unites.index') }}" class="hover:text-[#E31E24]">Unités</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium">Créer</span>
    </nav>

    <div class="bg-white rounded-2xl shadow-lg p-8 max-w-2xl">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Nouvelle Unité de Mesure</h1>

        <form action="{{ route('unites.store') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <x-icon name="scale" class="text-[#D7232A] mr-2" />Nom *
                </label>
                <input type="text" name="nom" value="{{ old('nom') }}" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent outline-hidden {{ $errors->has('nom') ? 'border-red-500' : '' }}" placeholder="Ex: Kilogramme, Litre, Mètre">
                @error('nom')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <x-icon name="code-xml" class="text-[#D7232A] mr-2" />Symbole *
                </label>
                <input type="text" name="symbole" value="{{ old('symbole') }}" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent outline-hidden {{ $errors->has('symbole') ? 'border-red-500' : '' }}" placeholder="Ex: kg, L, m">
                @error('symbole')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <x-icon name="pencil" class="text-[#D7232A] mr-2" />Description
                </label>
                <textarea name="description" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent outline-hidden" rows="4" placeholder="Décrivez cette unité...">{{ old('description') }}</textarea>
            </div>

            <div class="flex items-center space-x-3">
                <input type="checkbox" name="is_active" id="is_active" value="1" checked class="w-4 h-4 text-[#E31E24] border-gray-300 rounded focus:ring-[#D7232A]">
                <label for="is_active" class="text-gray-700 font-medium">Actif</label>
            </div>

            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <button type="submit" class="flex-1 px-6 py-3 bg-[#E31E24] text-white font-bold rounded-xl hover:bg-[#C71A20] transition-all flex items-center justify-center space-x-2">
                    <x-icon name="save" class="" />
                    <span>Créer</span>
                </button>
                <a href="{{ route('unites.index') }}" class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-300 transition-all flex items-center justify-center space-x-2">
                    <x-icon name="x" class="" />
                    <span>Annuler</span>
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
