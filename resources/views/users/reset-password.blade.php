@extends('layouts.admin')

@section('title', 'Réinitialiser Mot de Passe - StratAction Consulting')
@section('header', 'Réinitialiser Mot de Passe')

@section('content')
<div class="max-w-2xl mx-auto">
    <nav class="flex mb-6 text-sm text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
        <span class="mx-2">/</span>
        <a href="{{ route('users.index') }}" class="hover:text-[#E31E24]">Utilisateurs</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium">Réinitialiser mot de passe</span>
    </nav>

    <div class="overflow-hidden rounded-2xl border border-[#E7E5E1] bg-white">
        <div class="border-b border-[#E7E5E1] bg-white px-5 py-6 sm:px-8">
            <h2 class="text-xl font-semibold tracking-tight text-gray-900 sm:text-2xl">Réinitialiser le Mot de Passe</h2>
            <p class="mt-1 text-sm text-gray-500">{{ $user->name }} ({{ $user->email }})</p>
        </div>

        <form action="{{ route('users.update-password', $user->id) }}" method="POST" class="space-y-6 p-5 sm:p-8">
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

            <!-- Nouveau Mot de passe -->
            <div class="space-y-2">
                <label for="password" class="block text-sm font-semibold text-gray-700">Nouveau Mot de Passe</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <x-icon name="lock-keyhole" class="text-gray-400" />
                    </div>
                    <input type="password" id="password" name="password" required 
                        class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent transition-all outline-hidden"
                        placeholder="••••••••">
                </div>
                <p class="text-xs text-gray-500 mt-1">12 caractères minimum, avec majuscule, minuscule, chiffre et symbole.</p>
            </div>

            <!-- Confirmer Mot de passe -->
            <div class="space-y-2">
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700">Confirmer le Mot de Passe</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <x-icon name="lock-keyhole" class="text-gray-400" />
                    </div>
                    <input type="password" id="password_confirmation" name="password_confirmation" required 
                        class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent transition-all outline-hidden"
                        placeholder="••••••••">
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-100">
                <a href="{{ route('users.index') }}" class="px-6 py-3 text-sm font-semibold text-gray-600 hover:text-gray-900 transition">
                    Annuler
                </a>
                <button type="submit" 
                    class="px-8 py-3 bg-[#E31E24] text-white font-bold rounded-xl shadow-lg hover:bg-[#C71A20] hover:shadow-red-500/30 hover:-translate-y-0.5 transition-all active:scale-95">
                    Mettre à jour le mot de passe
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
