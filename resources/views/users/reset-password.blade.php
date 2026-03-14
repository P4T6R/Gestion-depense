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

    <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
        <div class="bg-gradient-to-r from-[#E31E24] to-[#C71A20] px-8 py-6">
            <h2 class="text-2xl font-bold text-white">Réinitialiser le Mot de Passe</h2>
            <p class="text-blue-100 mt-1 text-sm">{{ $user->name }} ({{ $user->email }})</p>
        </div>

        <form action="{{ route('users.update-password', $user->id) }}" method="POST" class="p-8 space-y-6">
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

            <!-- Nouveau Mot de passe -->
            <div class="space-y-2">
                <label for="password" class="block text-sm font-semibold text-gray-700">Nouveau Mot de Passe</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fa-solid fa-lock text-gray-400"></i>
                    </div>
                    <input type="password" id="password" name="password" required 
                        class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
                        placeholder="••••••••">
                </div>
                <p class="text-xs text-gray-500 mt-1">Minimum 8 caractères</p>
            </div>

            <!-- Confirmer Mot de passe -->
            <div class="space-y-2">
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700">Confirmer le Mot de Passe</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fa-solid fa-lock text-gray-400"></i>
                    </div>
                    <input type="password" id="password_confirmation" name="password_confirmation" required 
                        class="block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
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
