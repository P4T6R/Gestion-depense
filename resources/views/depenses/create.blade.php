@extends('layouts.admin')

@section('title', 'Créer Dépense - StratAction Consulting')
@section('header', 'Créer une Dépense')

@section('content')
<div class="space-y-6" x-data="depenseMultiLignes()" x-cloak>
    <!-- Breadcrumb -->
    <nav class="flex text-sm text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
        <span class="mx-2">/</span>
        <a href="{{ route('depenses.index') }}" class="hover:text-[#E31E24]">Dépenses</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium">Créer</span>
    </nav>

    <div class="bg-white rounded-2xl shadow-lg p-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8 flex items-center">
            <i class="fa-solid fa-file-invoice mr-3 text-[#E31E24]"></i>
            Nouvelle Facture - Multi-articles
        </h1>

        <form action="{{ route('depenses.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf

            <!-- Info Générale Facture -->
            <div class="grid md:grid-cols-2 gap-6 p-6 bg-gray-50 rounded-xl">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <i class="fa-solid fa-file-invoice text-[#E31E24] mr-2"></i>
                        Informations Facture
                    </h3>
                </div>

                <!-- Chantier -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fa-solid fa-building text-[#E31E24] mr-2"></i>Chantier *
                    </label>
                    <select name="chantier_id" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500">
                        <option value="">Sélectionner un chantier</option>
                        @foreach($chantiers as $chantier)
                            <option value="{{ $chantier->id }}">{{ $chantier->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Numéro Facture -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fa-solid fa-hashtag text-blue-500 mr-2"></i>N° Facture
                    </label>
                    <input type="text" name="numero_facture" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500" placeholder="FACT-2026-001">
                </div>

                <!-- Fournisseur -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fa-solid fa-truck text-purple-500 mr-2"></i>Fournisseur
                    </label>
                    <input type="text" name="fournisseur" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500" placeholder="Quincaillerie ABC">
                </div>

                <!-- Date Facture -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fa-solid fa-calendar text-green-500 mr-2"></i>Date Facture
                    </label>
                    <input type="date" name="date_facture" value="{{ date('Y-m-d') }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <!-- Articles / Lignes de la Facture -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center">
                        <i class="fa-solid fa-list text-[#E31E24] mr-2"></i>
                        Articles de la Facture
                    </h3>
                    <button type="button" @click="ajouterLigne()" class="px-4 py-2 bg-green-500 text-white rounded-xl hover:bg-green-600 transition-all flex items-center space-x-2">
                        <i class="fa-solid fa-plus"></i>
                        <span>Ajouter un article</span>
                    </button>
                </div>

                <!-- Liste des lignes -->
                <div class="space-y-4">
                    <template x-for="(ligne, index) in lignes" :key="'ligne-' + index">
                        <div class="p-6 border-2 border-dashed border-gray-300 rounded-xl hover:border-blue-500 transition-all">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="font-bold text-gray-700">Article <span x-text="index + 1"></span></h4>
                                <button type="button" @click="retirerLigne(index)" x-show="lignes.length > 1" class="text-red-500 hover:text-red-700">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>

                            <div class="grid md:grid-cols-5 gap-4">
                                <!-- Désignation -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Désignation *</label>
                                    <input type="text" x-model="ligne.designation" :name="'lignes[' + index + '][designation]'" required class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500" placeholder="Ex: Ciment CPJ 35">
                                </div>

                                <!-- Catégorie -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Catégorie</label>
                                    <select x-model="ligne.categorie_id" :name="'lignes[' + index + '][categorie_depense_id]'" class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500">
                                        <option value="">--</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->nom }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Unité -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Unité</label>
                                    <select x-model="ligne.unite_id" :name="'lignes[' + index + '][unite_mesure_id]'" class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500">
                                        <option value="">--</option>
                                        @foreach($unites as $unite)
                                            <option value="{{ $unite->id }}">{{ $unite->nom }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Quantité -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Quantité *</label>
                                    <input type="number" x-model.number="ligne.quantite" :name="'lignes[' + index + '][quantite]'" required step="0.01" min="0.01" class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500">
                                </div>

                                <!-- Prix Unitaire -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Prix Unit. (F) *</label>
                                    <input type="number" x-model.number="ligne.prix_unitaire" :name="'lignes[' + index + '][prix_unitaire]'" required step="0.01" min="0" class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500">
                                </div>

                                <!-- Montant (auto) -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Montant (F)</label>
                                    <div class="px-3 py-2 bg-blue-50 border-2 border-blue-200 rounded-xl font-bold text-blue-700" x-text="formatPrice(ligne.quantite * ligne.prix_unitaire)"></div>
                                </div>

                                <!-- Observations -->
                                <div class="md:col-span-4">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Observations</label>
                                    <input type="text" x-model="ligne.observations" :name="'lignes[' + index + '][observations]'" class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500" placeholder="Notes sur cet article...">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Total Général -->
                <div class="p-6 bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl border-2 border-blue-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-receipt text-3xl text-[#E31E24]"></i>
                            <div>
                                <p class="text-sm text-gray-600">TOTAL FACTURE</p>
                                <p class="text-3xl font-bold text-[#E31E24]" x-text="formatPrice(totalFacture)"></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Nombre d'articles</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="lignes.length"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Justificatif -->
            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <i class="fa-solid fa-camera text-teal-500 mr-2"></i>Photo de la Facture
                </label>
                <input type="file" name="justificatif" accept="image/*,application/pdf" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500">
                <p class="text-sm text-gray-500 mt-2">Une seule photo pour toute la facture</p>
            </div>

            <!-- Observations Générales -->
            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <i class="fa-solid fa-pen text-purple-500 mr-2"></i>Observations Générales
                </label>
                <textarea name="observations" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500" rows="3" placeholder="Notes sur cette facture..."></textarea>
            </div>

            <!-- Boutons -->
            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <button type="submit" class="flex-1 px-6 py-3 bg-[#E31E24] text-white font-bold rounded-xl hover:bg-[#C71A20] transition-all flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-save"></i>
                    <span>Enregistrer la Facture</span>
                </button>
                <a href="{{ route('depenses.index') }}" class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-300 transition-all flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-times"></i>
                    <span>Annuler</span>
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function depenseMultiLignes() {
    return {
        lignes: [
            {
                designation: '',
                categorie_id: '',
                unite_id: '',
                quantite: 1,
                prix_unitaire: 0,
                observations: ''
            }
        ],
        
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
                return total + (ligne.quantite * ligne.prix_unitaire);
            }, 0);
        },
        
        formatPrice(price) {
            return new Intl.NumberFormat('fr-FR').format(price || 0) + ' F';
        }
    }
}
</script>
@endsection
