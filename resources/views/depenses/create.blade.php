@extends('layouts.admin')

@section('title', (isset($depense) ? 'Modifier' : 'Créer').' Dépense - StratAction Consulting')
@section('header', isset($depense) ? 'Modifier la facture' : 'Créer une Dépense')

@section('content')
@php
    $initialLignes = collect(old('lignes', isset($depense) ? $depense->lignes->toArray() : [
        ['designation' => '', 'quantite' => 1, 'prix_unitaire' => 0],
    ]))->map(fn ($ligne) => [
        'designation' => $ligne['designation'] ?? '',
        'categorie_id' => $ligne['categorie_depense_id'] ?? '',
        'unite_id' => $ligne['unite_mesure_id'] ?? '',
        'quantite' => $ligne['quantite'] ?? 1,
        'prix_unitaire' => $ligne['prix_unitaire'] ?? 0,
        'observations' => $ligne['observations'] ?? '',
    ])->values();
@endphp
@if($errors->any())
<div role="alert" class="mb-4 rounded-xl bg-red-50 p-4 text-red-800">
    <p class="font-semibold">Veuillez corriger les champs suivants :</p>
    <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif
<div class="space-y-6" x-data="depenseMultiLignes()" x-cloak>
    <!-- Breadcrumb -->
    <nav class="flex text-sm text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
        <span class="mx-2">/</span>
        <a href="{{ route('depenses.index') }}" class="hover:text-[#E31E24]">Dépenses</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium">{{ isset($depense) ? 'Modifier' : 'Créer' }}</span>
    </nav>

    @if(isset($depense) && $depense->statut_validation === 'rejetee')
    <section role="status" class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-950" aria-label="Motif du rejet">
        <h2 class="font-semibold">Facture rejetée : correction demandée</h2>
        <p class="mt-2 whitespace-pre-line text-sm">{{ $depense->motif_rejet }}</p>
        <p class="mt-3 text-sm">Enregistrez vos corrections pour soumettre à nouveau la facture au directeur.</p>
    </section>
    @endif

    <div class="rounded-2xl border border-[#E7E5E1] bg-white p-4 sm:p-8">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 mb-8 flex items-start sm:items-center">
            <x-icon name="file-text" class="mr-3 text-[#D7232A]" />
            {{ isset($depense) ? 'Modifier la facture' : 'Nouvelle Facture - Multi-articles' }}
        </h1>

        <form action="{{ isset($depense) ? route('depenses.update', $depense) : route('depenses.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf
            @isset($depense) @method('PUT') @endisset

            <!-- Info Générale Facture -->
            <div class="grid md:grid-cols-2 gap-6 p-4 sm:p-6 bg-gray-50 rounded-xl">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <x-icon name="file-text" class="text-[#D7232A] mr-2" />
                        Informations Facture
                    </h3>
                </div>

                <!-- Chantier -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        <x-icon name="building-2" class="text-[#D7232A] mr-2" />Chantier *
                    </label>
                    <select name="chantier_id" aria-label="Chantier" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A]">
                        <option value="">Sélectionner un chantier</option>
                        @foreach($chantiers as $chantier)
                            <option value="{{ $chantier->id }}" @selected(old('chantier_id', $depense->chantier_id ?? null) == $chantier->id)>{{ $chantier->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Numéro Facture -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        <x-icon name="hash" class="text-[#D7232A] mr-2" />N° Facture
                    </label>
                    <input type="text" name="numero_facture" aria-label="Numéro de facture" value="{{ old('numero_facture', $depense->numero_facture ?? '') }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A]" placeholder="FACT-2026-001">
                </div>

                <!-- Fournisseur -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        <x-icon name="truck" class="text-[#D7232A] mr-2" />Fournisseur
                    </label>
                    <input type="text" name="fournisseur" aria-label="Fournisseur" value="{{ old('fournisseur', $depense->fournisseur ?? '') }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A]" placeholder="Quincaillerie ABC">
                </div>

                <!-- Date Facture -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        <x-icon name="calendar-days" class="text-[#D7232A] mr-2" />Date Facture
                    </label>
                    <input type="date" name="date_facture" aria-label="Date de facture" value="{{ old('date_facture', isset($depense) ? $depense->date_facture?->format('Y-m-d') : date('Y-m-d')) }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A]">
                </div>
            </div>

            <!-- Articles / Lignes de la Facture -->
            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center">
                        <x-icon name="list" class="text-[#D7232A] mr-2" />
                        Articles de la Facture
                    </h3>
                    <button type="button" @click="ajouterLigne()" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-[#D7232A] bg-white px-4 py-2 text-sm font-semibold text-[#C31F28] hover:bg-[#F8EEEE]">
                        <x-icon name="plus" class="" />
                        <span>Ajouter un article</span>
                    </button>
                </div>

                <!-- Liste des lignes -->
                <div class="space-y-4">
                    <template x-for="(ligne, index) in lignes" :key="'ligne-' + index">
                        <div class="rounded-xl border border-gray-200 bg-white p-4 transition-colors hover:border-gray-300 sm:p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="font-bold text-gray-700">Article <span x-text="index + 1"></span></h4>
                                <button type="button" aria-label="Supprimer cet article" @click="retirerLigne(index)" x-show="lignes.length > 1" class="text-red-500 hover:text-red-700">
                                    <x-icon name="trash-2" class="" />
                                </button>
                            </div>

                            <div class="grid md:grid-cols-5 gap-4">
                                <!-- Désignation -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Désignation *</label>
                                    <input type="text" aria-label="Désignation de l’article" x-model="ligne.designation" :name="'lignes[' + index + '][designation]'" required class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A]" placeholder="Ex: Ciment CPJ 35">
                                </div>

                                <!-- Catégorie -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Catégorie</label>
                                    <select aria-label="Catégorie de l’article" x-model="ligne.categorie_id" :name="'lignes[' + index + '][categorie_depense_id]'" class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A]">
                                        <option value="">--</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->nom }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Unité -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Unité</label>
                                    <select aria-label="Unité de mesure" x-model="ligne.unite_id" :name="'lignes[' + index + '][unite_mesure_id]'" class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A]">
                                        <option value="">--</option>
                                        @foreach($unites as $unite)
                                            <option value="{{ $unite->id }}">{{ $unite->nom }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Quantité -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Quantité *</label>
                                    <input type="number" aria-label="Quantité" x-model.number="ligne.quantite" :name="'lignes[' + index + '][quantite]'" required step="0.01" min="0.01" class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A]">
                                </div>

                                <!-- Prix Unitaire -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Prix Unit. (F) *</label>
                                    <input type="number" aria-label="Prix unitaire" x-model.number="ligne.prix_unitaire" :name="'lignes[' + index + '][prix_unitaire]'" required step="0.01" min="0" class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A]">
                                </div>

                                <!-- Montant (auto) -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Montant (F)</label>
                                    <div class="rounded-xl border border-gray-200 bg-[#F7F6F3] px-3 py-2 font-semibold text-gray-900" x-text="formatPrice(ligne.quantite * ligne.prix_unitaire)"></div>
                                </div>

                                <!-- Observations -->
                                <div class="md:col-span-4">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Observations</label>
                                    <input type="text" aria-label="Observations de l’article" x-model="ligne.observations" :name="'lignes[' + index + '][observations]'" class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A]" placeholder="Notes sur cet article...">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Total Général -->
                <div class="rounded-xl border border-[#E7E5E1] bg-[#F7F6F3] p-4 sm:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center space-x-3">
                            <x-icon name="receipt" class="size-8 text-[#D7232A]" />
                            <div>
                                <p class="text-sm text-gray-600">TOTAL FACTURE</p>
                                <p class="text-3xl font-bold text-[#E31E24]" x-text="formatPrice(totalFacture)"></p>
                            </div>
                        </div>
                        <div class="sm:text-right">
                            <p class="text-sm text-gray-600">Nombre d'articles</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="lignes.length"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Justificatif -->
            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <x-icon name="file-text" class="text-[#D7232A] mr-2" />Justificatif de la facture
                </label>
                <input type="file" name="justificatif" aria-label="Justificatif PDF ou image" accept="image/jpeg,image/png,application/pdf" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A]">
                <p class="text-sm text-gray-500 mt-2">Image JPG, PNG ou document PDF pour la facture entière.</p>
            </div>

            <!-- Observations Générales -->
            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <x-icon name="pencil" class="text-[#D7232A] mr-2" />Observations Générales
                </label>
                <textarea name="observations" aria-label="Observations de la facture" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A]" rows="3" placeholder="Notes sur cette facture...">{{ old('observations', $depense->observations ?? '') }}</textarea>
            </div>

            <!-- Boutons -->
            <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
                <button type="submit" class="flex-1 px-6 py-3 bg-[#E31E24] text-white font-bold rounded-xl hover:bg-[#C71A20] transition-all flex items-center justify-center space-x-2">
                    <x-icon name="save" class="" />
                    <span class="min-w-0 wrap-break-word">{{ isset($depense) && $depense->statut_validation === 'rejetee' ? 'Corriger et soumettre à nouveau' : (isset($depense) ? 'Enregistrer les modifications' : 'Enregistrer la Facture') }}</span>
                </button>
                <a href="{{ route('depenses.index') }}" class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-300 transition-all flex items-center justify-center space-x-2">
                    <x-icon name="x" class="" />
                    <span>Annuler</span>
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function depenseMultiLignes() {
    return {
        lignes: {{ Illuminate\Support\Js::from($initialLignes) }},

        ajouterLigne() {
            this.lignes.push({
                designation: '',
                categorie_id: '',
                unite_id: '',
                quantite: 1,
                prix_unitaire: 0,
                observations: ''
            });
        },
        
        retirerLigne(index) {
            if (this.lignes.length > 1) {
                this.lignes.splice(index, 1);
            }
        },
        
        get totalFacture() {
            return this.lignes.reduce((total, ligne) => {
                return total + Math.round((Number(ligne.quantite) * Number(ligne.prix_unitaire) + Number.EPSILON) * 100) / 100;
            }, 0);
        },
        
        formatPrice(price) {
            return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(price || 0) + ' F';
        }
    }
}
</script>
@endsection
