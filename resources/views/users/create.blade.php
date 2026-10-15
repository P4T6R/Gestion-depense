@extends('layouts.admin')

@section('title', 'Créer un Utilisateur - StratAction Consulting')
@section('header', 'Gestion des Utilisateurs')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="flex mb-6 text-sm text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium">Nouvel Utilisateur</span>
    </nav>

    <div class="overflow-hidden rounded-2xl border border-[#E7E5E1] bg-white">
        <div class="border-b border-[#E7E5E1] bg-white px-5 py-6 sm:px-8">
            <h2 class="text-xl font-semibold tracking-tight text-gray-900 sm:text-2xl">Ajouter un Utilisateur</h2>
            <p class="mt-1 text-sm text-gray-500">Remplissez les informations pour créer un nouveau compte.</p>
        </div>

        <form action="{{ route('users.store') }}" method="POST" class="space-y-6 p-5 sm:p-8">
            @csrf
            
            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <x-icon name="circle-alert" class="text-red-500" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                Veuillez corriger les erreurs suivantes :
                            </p>
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
                <!-- Nom Complet -->
                <div class="space-y-2">
                    <label for="name" class="block text-sm font-semibold text-gray-700">Nom Complet</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <x-icon name="user-round" class="text-gray-400" />
                        </div>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required 
                            class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent transition-all outline-hidden"
                            placeholder="Ex: Jean Dupont">
                    </div>
                </div>

                <!-- Email -->
                <div class="space-y-2">
                    <label for="email" class="block text-sm font-semibold text-gray-700">Adresse Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <x-icon name="mail" class="text-gray-400" />
                        </div>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required 
                            class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent transition-all outline-hidden"
                            placeholder="jean.dupont@example.com">
                    </div>
                </div>

                <!-- Rôle -->
                <div class="space-y-2">
                    <label for="role" class="block text-sm font-semibold text-gray-700">Rôle / Fonction</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <x-icon name="shield-half" class="text-gray-400" />
                        </div>
                        <select id="role" name="role" required 
                            class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent transition-all outline-hidden appearance-none">
                            <option value="">Sélectionner un rôle</option>
                            <option value="administrateur" {{ old('role') == 'administrateur' ? 'selected' : '' }}>Administrateur</option>
                            <option value="comptable" {{ old('role') == 'comptable' ? 'selected' : '' }}>Comptable</option>
                            <option value="chef_chantier" {{ old('role') == 'chef_chantier' ? 'selected' : '' }}>Chef de Chantier</option>
                            <option value="directeur" {{ old('role') == 'directeur' ? 'selected' : '' }}>Directeur</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <x-icon name="chevron-down" class="text-gray-400 size-3" />
                        </div>
                    </div>
                </div>

                <!-- Téléphone -->
                <div class="space-y-2">
                    <label for="phone" class="block text-sm font-semibold text-gray-700">Téléphone</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <x-icon name="phone" class="text-gray-400" />
                        </div>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                            class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent transition-all outline-hidden"
                            placeholder="+226 XX XX XX XX">
                    </div>
                </div>

                <!-- Mot de passe -->
                <div class="space-y-2">
                    <label for="password" class="block text-sm font-semibold text-gray-700">Mot de passe</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <x-icon name="lock-keyhole" class="text-gray-400" />
                        </div>
                        <input type="password" id="password" name="password" required 
                            class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent transition-all outline-hidden"
                            placeholder="••••••••">
                    </div>
                    <p class="text-xs text-gray-500">12 caractères minimum, avec majuscule, minuscule, chiffre et symbole.</p>
                </div>

                <!-- Confirmer Mot de passe -->
                <div class="space-y-2">
                    <label for="password_confirmation" class="block text-sm font-semibold text-gray-700">Confirmer le mot de passe</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <x-icon name="lock-keyhole" class="text-gray-400" />
                        </div>
                        <input type="password" id="password_confirmation" name="password_confirmation" required 
                            class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent transition-all outline-hidden"
                            placeholder="••••••••">
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-100">
                <a href="#" class="px-6 py-3 text-sm font-semibold text-gray-600 hover:text-gray-900 transition">
                    Annuler
                </a>
                <button type="submit" 
                    class="px-8 py-3 bg-[#E31E24] text-white font-bold rounded-xl shadow-lg hover:bg-[#C71A20] hover:shadow-red-500/30 hover:-translate-y-0.5 transition-all active:scale-95">
                    Créer l'utilisateur
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
