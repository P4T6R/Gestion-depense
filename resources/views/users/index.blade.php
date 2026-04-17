@extends('layouts.admin')

@section('title', 'Gestion des Utilisateurs - ARTS Constructions & Services')
@section('header', 'Gestion des Utilisateurs')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Bouton -->
    <div class="flex items-center justify-between">
        <nav class="flex text-sm text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900 font-medium">Utilisateurs</span>
        </nav>
        <a href="{{ route('users.create') }}" class="px-6 py-3 bg-gradient-to-r from-[#E31E24] to-[#C71A20] text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center gap-3">
            <i class="fa-solid fa-user-plus"></i>
            <span>Nouvel Utilisateur</span>
        </a>
    </div>

    <!-- Messages de succès -->
    @if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-xl">
        <div class="flex">
            <i class="fa-solid fa-check-circle text-green-500 mt-0.5"></i>
            <p class="ml-3 text-sm text-green-700">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    <!-- Tableau des utilisateurs -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-[#E31E24] to-[#C71A20] text-white">
                    <tr>
                        <th class="px-8 py-5 text-left text-xs font-bold uppercase tracking-wider">Nom</th>
                        <th class="px-8 py-5 text-left text-xs font-bold uppercase tracking-wider">Email</th>
                        <th class="px-8 py-5 text-left text-xs font-bold uppercase tracking-wider">Rôle</th>
                        <th class="px-8 py-5 text-left text-xs font-bold uppercase tracking-wider">Téléphone</th>
                        <th class="px-8 py-5 text-left text-xs font-bold uppercase tracking-wider">Statut</th>
                        <th class="px-8 py-5 text-right text-xs font-bold uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                    <tr class="group transition-all duration-200 hover:bg-gray-50">
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-[#E31E24] to-[#C71A20] rounded-xl flex items-center justify-center text-white font-bold shadow-sm">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <span class="font-bold text-gray-900">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-5 text-gray-600">{{ $user->email }}</td>
                        <td class="px-8 py-5">
                            @php
                                $roleColors = [
                                    'administrateur' => 'bg-red-100 text-red-700',
                                    'comptable' => 'bg-green-100 text-green-700',
                                    'chef_chantier' => 'bg-yellow-100 text-yellow-700',
                                    'directeur' => 'bg-purple-100 text-purple-700',
                                ];
                                $roleClass = $roleColors[$user->role] ?? 'bg-gray-100 text-gray-700';
                            @endphp
                            <span class="px-4 py-2 rounded-lg text-xs font-bold {{ $roleClass }}">
                                {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                            </span>
                        </td>
                        <td class="px-8 py-5 text-gray-600">{{ $user->phone ?? 'N/A' }}</td>
                        <td class="px-8 py-5">
                            @if($user->is_active)
                                <span class="px-4 py-2 bg-green-100 text-green-700 rounded-lg text-xs font-bold">Actif</span>
                            @else
                                <span class="px-4 py-2 bg-red-100 text-red-700 rounded-lg text-xs font-bold">Inactif</span>
                            @endif
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('users.edit', $user->id) }}" title="Modifier" class="p-2.5 text-gray-600 hover:text-[#E31E24] hover:bg-red-50 rounded-lg transition-all duration-200">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="{{ route('users.reset-password', $user->id) }}" title="Réinitialiser mot de passe" class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition">
                                <i class="fa-solid fa-key"></i>
                            </a>
                            <form action="{{ route('users.toggle-active', $user->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" title="{{ $user->is_active ? 'Désactiver' : 'Activer' }}" class="p-2 transition" :class="$user->is_active ? 'text-green-600 hover:bg-green-50' : 'text-red-600 hover:bg-red-50'">
                                    <i class="fa-solid {{ $user->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Supprimer" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fa-solid fa-inbox text-3xl mb-3"></i>
                            <p>Aucun utilisateur trouvé.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
