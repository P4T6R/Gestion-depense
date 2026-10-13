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

    <div class="overflow-hidden rounded-2xl border border-[#E7E5E1] bg-white">
        <div class="border-b border-[#E7E5E1] bg-white px-5 py-6 sm:px-8">
            <h2 class="text-xl font-semibold tracking-tight text-gray-900 sm:text-2xl">Modifier le Chantier</h2>
            <p class="mt-1 text-sm text-gray-500">{{ $chantier->nom }}</p>
        </div>

        <form action="{{ route('chantiers.update', $chantier->id) }}" method="POST" class="space-y-6 p-5 sm:p-8">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <x-icon name="circle-alert" class="text-red-500" />
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
                            <x-icon name="building-2" class="text-gray-400" />
                        </div>
                        <input type="text" id="nom" name="nom" value="{{ old('nom', $chantier->nom) }}" required
                            class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent transition-all outline-hidden">
                    </div>
                </div>

                <!-- Lieu -->
                <div class="space-y-2">
                    <label for="lieu" class="block text-sm font-semibold text-gray-700">Localisation</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <x-icon name="map-pin" class="text-gray-400" />
                        </div>
                        <input type="text" id="lieu" name="lieu" value="{{ old('lieu', $chantier->lieu) }}" required
                            class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent transition-all outline-hidden">
                    </div>
                </div>

                <!-- Budget Prévisionnel -->
                <div class="space-y-2">
                    <label for="budget_alloue" class="block text-sm font-semibold text-gray-700">Budget Prévisionnel (FCFA)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <x-icon name="banknote" class="text-gray-400" />
                        </div>
                        <input type="number" id="budget_alloue" name="budget_alloue" value="{{ old('budget_alloue', $chantier->budget_alloue) }}" required
                            class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent transition-all outline-hidden">
                    </div>
                </div>

                <!-- Date de Début -->
                <div class="space-y-2">
                    <label for="date_debut" class="block text-sm font-semibold text-gray-700">Date de Début</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <x-icon name="calendar-days" class="text-gray-400" />
                        </div>
                        <input type="date" id="date_debut" name="date_debut" value="{{ old('date_debut', $chantier->date_debut?->format('Y-m-d')) }}" required
                            class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent transition-all outline-hidden">
                    </div>
                </div>

                <!-- Statut -->
                <div class="space-y-2">
                    <label for="statut" class="block text-sm font-semibold text-gray-700">Statut</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <x-icon name="flag" class="text-gray-400" />
                        </div>
                        <select id="statut" name="statut" required
                            class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent transition-all outline-hidden appearance-none">
                            <option value="actif" {{ old('statut', $chantier->statut) == 'actif' ? 'selected' : '' }}>Actif</option>
                            <option value="en_pause" {{ old('statut', $chantier->statut) == 'en_pause' ? 'selected' : '' }}>En pause</option>
                            <option value="termine" {{ old('statut', $chantier->statut) == 'termine' ? 'selected' : '' }}>Terminé</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <x-icon name="chevron-down" class="text-gray-400 size-3" />
                        </div>
                    </div>
                </div>

                <!-- Chef de Chantier -->
                <div class="space-y-2">
                    <label for="chef_id" class="block text-sm font-semibold text-gray-700">Chef de Chantier Responsable</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <x-icon name="user-round" class="text-gray-400" />
                        </div>
                        <select id="chef_id" name="chef_id" required
                            class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent transition-all outline-hidden appearance-none">
                            <option value="">Sélectionner un responsable</option>
                            @foreach($chefs as $chef)
                                <option value="{{ $chef->id }}" {{ old('chef_id', $chantier->chefsChantier->first()->id ?? null) == $chef->id ? 'selected' : '' }}>{{ $chef->name }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <x-icon name="chevron-down" class="text-gray-400 size-3" />
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="md:col-span-2 space-y-2">
                    <label for="description" class="block text-sm font-semibold text-gray-700">Description / Notes</label>
                    <textarea id="description" name="description" rows="4"
                        class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent transition-all outline-hidden">{{ old('description', $chantier->description) }}</textarea>
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
