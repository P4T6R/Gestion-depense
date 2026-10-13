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
        <a href="{{ route('users.create') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-[#D7232A] px-5 py-2.5 font-semibold text-white transition-colors hover:bg-[#BA1E26]">
            <x-icon name="user-round-plus" class="" />
            <span>Nouvel Utilisateur</span>
        </a>
    </div>

    <!-- Messages de succès -->
    @if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-xl">
        <div class="flex">
            <x-icon name="circle-check" class="text-green-500 mt-0.5" />
            <p class="ml-3 text-sm text-green-700">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    <!-- Tableau des utilisateurs -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-[#FAF9F7] text-[#444]">
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
                                <div class="w-12 h-12 bg-[#F8EEEE] rounded-xl flex items-center justify-center text-[#C31F28] font-bold shadow-xs">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <span class="font-bold text-gray-900">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-5 text-gray-600">{{ $user->email }}</td>
                        <td class="px-8 py-5">
                            @php
                                $roleColors = [
                                    'administrateur' => 'bg-[#F5F4F2] text-[#444]',
                                    'comptable' => 'bg-[#F5F4F2] text-[#444]',
                                    'chef_chantier' => 'bg-[#F5F4F2] text-[#444]',
                                    'directeur' => 'bg-[#F5F4F2] text-[#444]',
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
                                <a href="{{ route('users.edit', $user->id) }}" title="Modifier" class="p-2.5 text-[#555] hover:text-[#C31F28] hover:bg-[#F8EEEE] rounded-lg transition-all duration-200">
                                <x-icon name="square-pen" class="" />
                            </a>
                            <a href="{{ route('users.reset-password', $user->id) }}" title="Réinitialiser mot de passe" class="p-2 text-[#555] hover:bg-[#F8EEEE] hover:text-[#C31F28] rounded-lg transition">
                                <x-icon name="key-round" class="" />
                            </a>
                            <form action="{{ route('users.toggle-active', $user->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" title="{{ $user->is_active ? 'Désactiver' : 'Activer' }}" class="p-2 text-[#555] transition hover:bg-[#F8EEEE] hover:text-[#C31F28]">
                                    <x-icon :name="$user->is_active ? 'toggle-right' : 'toggle-left'" />
                                </button>
                            </form>
                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Supprimer" class="p-2 text-[#555] hover:bg-[#F8EEEE] hover:text-[#C31F28] rounded-lg transition">
                                    <x-icon name="trash-2" class="" />
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <x-icon name="inbox" class="size-8 mb-3" />
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
