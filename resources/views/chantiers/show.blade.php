@extends('layouts.admin')

@section('title', 'Détails Chantier - StratAction Consulting')
@section('header', 'Détails du Chantier')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex text-sm text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
        <span class="mx-2">/</span>
        <a href="{{ route('chantiers.index') }}" class="hover:text-[#E31E24]">Chantiers</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium">{{ $chantier->nom }}</span>
    </nav>

    <!-- En-tête avec actions -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl">{{ $chantier->nom }}</h1>
            <p class="text-gray-600 mt-2">
                <x-icon name="map-pin" class="text-[#D7232A] mr-2" />{{ $chantier->lieu }}
            </p>
        </div>
        @if(auth()->user()->role === 'administrateur')
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('chantiers.edit', $chantier->id) }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                <x-icon name="square-pen" class="" />
                <span>Modifier</span>
            </a>
            <form action="{{ route('chantiers.destroy', $chantier->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">
                    <x-icon name="trash-2" class="" />
                    <span>Supprimer</span>
                </button>
            </form>
        </div>
        @endif
    </div>

    <div class="grid min-w-0 gap-6 md:grid-cols-3">
        <!-- Informations principales -->
        <div class="min-w-0 space-y-6 md:col-span-2">
            <!-- Card - Informations Générales -->
            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <x-icon name="info" class="text-[#D7232A] mr-3" />
                    Informations Générales
                </h2>
                <div class="space-y-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="border-b border-gray-200 pb-4">
                            <p class="text-gray-600 text-sm">Date de Début</p>
                            <p class="text-gray-900 font-semibold">{{ $chantier->date_debut?->format('d/m/Y') ?? 'N/A' }}</p>
                        </div>
                        <div class="border-b border-gray-200 pb-4">
                            <p class="text-gray-600 text-sm">Statut</p>
                            @php
                                $statusColors = [
                                    'actif' => 'bg-green-100 text-green-700',
                                    'en_pause' => 'bg-yellow-100 text-yellow-700',
                                    'termine' => 'bg-gray-100 text-gray-700',
                                ];
                                $statusClass = $statusColors[$chantier->statut] ?? 'bg-gray-100 text-gray-700';
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">
                                {{ ucfirst($chantier->statut) }}
                            </span>
                        </div>
                    </div>
                    @if($chantier->description)
                    <div class="pt-4 border-t border-gray-200">
                        <p class="text-gray-600 text-sm">Description</p>
                        <p class="text-gray-900 mt-2">{{ $chantier->description }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Card - Budget -->
            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <x-icon name="wallet" class="text-[#D7232A] mr-3" />
                    Budget & Dépenses
                </h2>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-gray-600 text-sm">Budget Alloué</p>
                            <p class="text-gray-900 font-semibold">{{ number_format($chantier->budget_alloue, 0, ',', ' ') }} F</p>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-gray-600 text-sm">Budget Consommé</p>
                            <p class="text-gray-900 font-semibold">{{ number_format($chantier->budget_consomme, 0, ',', ' ') }} F</p>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-gray-200">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-gray-600 text-sm">Budget Restant</p>
                            <p class="text-gray-900 font-semibold">{{ number_format($chantier->budget_alloue - $chantier->budget_consomme, 0, ',', ' ') }} F</p>
                        </div>
                        @php
                            $percentageUsed = $chantier->budget_alloue > 0 ? ($chantier->budget_consomme / $chantier->budget_alloue) * 100 : 0;
                        @endphp
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full bg-[#D7232A]" style="width: {{ min($percentageUsed, 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">{{ number_format($percentageUsed, 1) }}% consommé</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar - Chef de Chantier -->
        <div class="space-y-6">
            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <x-icon name="user-round" class="text-[#D7232A] mr-3" />
                    Chef de Chantier
                </h2>
                @php
                    $chef = $chantier->chefsChantier->first();
                @endphp
                @if($chef)
                <div class="flex min-w-0 items-center gap-4">
                    <div class="flex size-12 items-center justify-center rounded-full bg-[#F8EEEE] text-lg font-bold text-[#C31F28]">
                        {{ substr($chef->name, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900">{{ $chef->name }}</p>
                        <p class="break-all text-sm text-gray-600">{{ $chef->email }}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            <x-icon name="phone" class="mr-1" />{{ $chef->phone ?? 'N/A' }}
                        </p>
                    </div>
                </div>
                @else
                <p class="text-gray-500 italic">Non assigné</p>
                @endif
            </div>

            <!-- Statistiques rapides -->
            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-8">
                <h2 class="mb-6 flex items-center text-xl font-bold text-gray-900">
                    <x-icon name="chart-no-axes-column" class="mr-3" />
                    Dépenses
                </h2>
                <div class="space-y-4">
                    <div class="rounded-xl bg-[#F7F6F3] p-4">
                        <p class="text-sm text-gray-500">Total des dépenses</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $chantier->depenses_count }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des dépenses récentes -->
    @if($chantier->depenses->count() > 0)
    <div class="overflow-hidden rounded-2xl border border-[#E7E5E1] bg-white">
        <div class="px-8 py-6 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">Dernières Dépenses</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Description</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Montant</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($chantier->depenses->take(5) as $depense)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-gray-900"><a href="{{ route('depenses.show', $depense) }}" class="font-medium hover:text-[#C31F28] hover:underline">{{ $depense->libelle ?? 'N/A' }}</a></td>
                        <td class="px-6 py-4 font-semibold text-gray-900">{{ number_format($depense->prix_total, 0, ',', ' ') }} F</td>
                        <td class="px-6 py-4 text-gray-600 text-sm">{{ $depense->created_at->format('d/m/Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
