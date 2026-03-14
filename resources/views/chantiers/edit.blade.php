@extends('layouts.admin')

@section('title', 'Modifier Chantier - StratAction Consulting')
@section('header', 'Modifier Chantier')

@section('content')
<div class="max-w-4xl mx-auto">
    <nav class="flex mb-6 text-sm text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
        <span class="mx-2">/</span>
        <a href="{{ route('chantiers.index') }}" class="hover:text-[#E31E24]">Chantiers</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium">Modifier</span>
    </nav>

    <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
        <div class="bg-gradient-to-r from-[#E31E24] to-[#C71A20] px-8 py-6">
            <h2 class="text-2xl font-bold text-white">Modifier le Chantier</h2>
            <p class="text-blue-100 mt-1 text-sm">{{ $chantier->nom }}</p>
        </div>

        <form action="{{ route('chantiers.update', $chantier->id) }}" method="POST" class="p-8 space-y-6">
            @csrf
            @method('PUT')
            
            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fa-solid fa-circle-exclamation text-red-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">Veuillez corriger les erreurs suivantes :</p>
                            <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Nom du Chantier -->
                <div class="md:col-span-2 space-y-2">
                    <label for="nom" class="block text-sm font-semibold text-gray-700">Nom du Chantier</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-building text-gray-400"></i>
                        </div>
                        <input type="text" id="nom" name="nom" value="{{ old('nom', $chantier->nom) }}" required 
                            class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none">
                    </div>
                </div>

                <!-- Lieu -->
                <div class="space-y-2">
                    <label for="lieu" class="block text-sm font-semibold text-gray-700">Localisation</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-location-dot text-gray-400"></i>
                        </div>
                        <input type="text" id="lieu" name="lieu" value="{{ old('lieu', $chantier->lieu) }}" required 
                            class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none">
                    </div>
                </div>

                <!-- Budget Prévisionnel -->
                <div class="space-y-2">
                    <label for="budget_alloue" class="block text-sm font-semibold text-gray-700">Budget Prévisionnel (FCFA)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-money-bill text-gray-400"></i>
                        </div>
                        <input type="number" id="budget_alloue" name="budget_alloue" value="{{ old('budget_alloue', $chantier->budget_alloue) }}" required
                            class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none">
                    </div>
                </div>

                <!-- Date de Début -->
                <div class="space-y-2">
                    <label for="date_debut" class="block text-sm font-semibold text-gray-700">Date de Début</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-calendar-days text-gray-400"></i>
                        </div>
                        <input type="date" id="date_debut" name="date_debut" value="{{ old('date_debut', $chantier->date_debut->format('Y-m-d')) }}" required 
                            class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none">
                    </div>
                </div>

                <!-- Statut -->
                <div class="space-y-2">
                    <label for="statut" class="block text-sm font-semibold text-gray-700">Statut</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-flag text-gray-400"></i>
                        </div>
                        <select id="statut" name="statut" required 
                            class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none appearance-none">
                            <option value="actif" {{ old('statut', $chantier->statut) == 'actif' ? 'selected' : '' }}>Actif</option>
                            <option value="suspendu" {{ old('statut', $chantier->statut) == 'suspendu' ? 'selected' : '' }}>Suspendu</option>
                            <option value="termine" {{ old('statut', $chantier->statut) == 'termine' ? 'selected' : '' }}>Terminé</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                </div>

                <!-- Chef de Chantier -->
                <div class="space-y-2">
                    <label for="chef_id" class="block text-sm font-semibold text-gray-700">Chef de Chantier Responsable</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-user-tie text-gray-400"></i>
                        </div>
                        <select id="chef_id" name="chef_id" required 
                            class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none appearance-none">
                            <option value="">Sélectionner un responsable</option>
                            @foreach($chefs as $chef)
                                <option value="{{ $chef->id }}" {{ old('chef_id', $chantier->chefsChantier->first()->id ?? null) == $chef->id ? 'selected' : '' }}>{{ $chef->name }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="md:col-span-2 space-y-2">
                    <label for="description" class="block text-sm font-semibold text-gray-700">Description / Notes</label>
                    <textarea id="description" name="description" rows="4" 
                        class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none">{{ old('description', $chantier->description) }}</textarea>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-100">
                <a href="{{ route('chantiers.index') }}" class="px-6 py-3 text-sm font-semibold text-gray-600 hover:text-gray-900 transition">
                    Annuler
                </a>
                <button type="submit" 
                    class="px-8 py-3 bg-[#E31E24] text-white font-bold rounded-xl shadow-lg hover:bg-[#C71A20] hover:shadow-red-500/30 hover:-translate-y-0.5 transition-all active:scale-95">
                    Modifier le chantier
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
