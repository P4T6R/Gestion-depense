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
                <h2 class="text-xl font-bold text-gray-900 mb-2 flex items-center">
                    <x-icon name="wallet" class="text-[#D7232A] mr-3" />
                    Situation financière
                </h2>
                <p class="mb-6 text-sm text-gray-600">Le solde déduit les factures validées, même si elles ne sont pas encore payées.</p>
                @php
                    $financeCards = [
                        ['label' => 'Budget alloué', 'value' => $synthese['budget_alloue'], 'icon' => 'wallet', 'note' => 'Montant autorisé pour ce chantier'],
                        ['label' => 'Factures en attente', 'value' => $synthese['factures_en_attente'], 'icon' => 'clock-3', 'note' => 'En cours de décision ou de validation'],
                        ['label' => 'Dépenses validées', 'value' => $synthese['depenses_validees'], 'icon' => 'check-check', 'note' => 'Engagées dans le budget'],
                        ['label' => 'Réellement payé', 'value' => $synthese['montant_paye'], 'icon' => 'banknote', 'note' => 'Paiements enregistrés et non annulés'],
                        ['label' => 'Reste à payer', 'value' => $synthese['reste_a_payer'], 'icon' => 'receipt', 'note' => 'Validé mais non réglé'],
                        ['label' => 'Solde disponible', 'value' => $synthese['solde_disponible'], 'icon' => 'coins', 'note' => 'Budget alloué moins dépenses validées'],
                    ];
                @endphp
                <dl class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($financeCards as $card)
                        <div class="rounded-xl border border-[#E7E5E1] bg-[#FCFBF9] p-4">
                            <dt class="flex items-center gap-2 text-sm font-medium text-gray-600"><x-icon :name="$card['icon']" class="size-4 text-[#C31F28]" />{{ $card['label'] }}</dt>
                            <dd class="mt-3 text-xl font-semibold tracking-tight text-gray-900">{{ number_format((float) $card['value'], 2, ',', ' ') }} <span class="text-sm font-medium">F</span></dd>
                            <p class="mt-1 text-xs text-gray-500">{{ $card['note'] }}</p>
                        </div>
                    @endforeach
                </dl>
                @if($synthese['factures_historiques_a_rapprocher'] > 0)
                    <p role="status" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950">{{ $synthese['factures_historiques_a_rapprocher'] }} ancienne(s) facture(s) marquée(s) payée(s) restent à rapprocher ({{ number_format((float) $synthese['historique_a_rapprocher'], 2, ',', ' ') }} F). Ce montant n’est pas inclus dans « Réellement payé ».</p>
                @endif
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

    <section class="overflow-hidden rounded-2xl border border-[#E7E5E1] bg-white" aria-labelledby="derniers-paiements-title">
        <div class="border-b border-[#E7E5E1] px-5 py-5 sm:px-8"><h2 id="derniers-paiements-title" class="flex items-center gap-2 text-xl font-semibold"><x-icon name="banknote" class="size-5 text-[#D7232A]" />Derniers paiements</h2><p class="mt-1 text-sm text-gray-600">Règlements enregistrés, rattachés à leurs factures.</p></div>
        @if($derniersPaiements->isNotEmpty())
            <div class="overflow-x-auto"><table class="w-full min-w-[620px] text-left text-sm"><caption class="sr-only">Derniers paiements du chantier</caption><thead class="bg-[#FAF9F7] text-gray-600"><tr><th scope="col" class="px-5 py-3">Facture</th><th scope="col" class="px-5 py-3">Fournisseur</th><th scope="col" class="px-5 py-3">Date</th><th scope="col" class="px-5 py-3">Référence</th><th scope="col" class="px-5 py-3 text-right">Montant</th></tr></thead><tbody class="divide-y divide-[#E7E5E1]">@foreach($derniersPaiements as $paiement)<tr><td class="px-5 py-3 font-semibold"><a href="{{ route('depenses.show', $paiement->depense) }}" class="hover:text-[#C31F28] hover:underline">{{ $paiement->depense?->numero_facture ?: $paiement->depense?->libelle }}</a></td><td class="px-5 py-3">{{ $paiement->depense?->fournisseur ?: 'Non renseigné' }}</td><td class="whitespace-nowrap px-5 py-3">{{ $paiement->date_paiement?->format('d/m/Y') }}</td><td class="px-5 py-3">{{ $paiement->reference }}</td><td class="whitespace-nowrap px-5 py-3 text-right font-semibold">{{ number_format((float) $paiement->montant, 2, ',', ' ') }} F</td></tr>@endforeach</tbody></table></div>
        @else
            <p class="px-5 py-7 text-sm text-gray-600 sm:px-8">Aucun paiement enregistré pour ce chantier.</p>
        @endif
    </section>
</div>
@endsection
