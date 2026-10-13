@extends('layouts.admin')

@section('title', 'Modifier Dépense - StratAction Consulting')
@section('header', 'Modifier une Dépense')

@section('content')
<div class="space-y-6">
    @if($errors->any())
    <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
        <p class="font-semibold">Veuillez corriger les champs suivants :</p>
        <ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif
    <!-- Breadcrumb -->
    <nav class="flex text-sm text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
        <span class="mx-2">/</span>
        <a href="{{ route('depenses.index') }}" class="hover:text-[#E31E24]">Dépenses</a>
        <span class="mx-2">/</span>
        <a href="{{ route('depenses.show', $depense->id) }}" class="hover:text-[#E31E24]">{{ $depense->libelle }}</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium">Modifier</span>
    </nav>

    @if($depense->statut_validation === 'rejetee')
    <section role="status" class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-950" aria-label="Motif du rejet">
        <h2 class="font-semibold">Facture rejetée : correction demandée</h2>
        <p class="mt-2 whitespace-pre-line text-sm">{{ $depense->motif_rejet }}</p>
        <p class="mt-3 text-sm">Enregistrez vos corrections pour soumettre à nouveau la facture au directeur.</p>
    </section>
    @endif

    <div class="bg-white rounded-2xl shadow-lg p-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Modifier la Dépense</h1>

        <form action="{{ route('depenses.update', $depense->id) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf
            @method('PUT')

            <!-- Sélection du Chantier -->
            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <x-icon name="building-2" class="text-[#D7232A] mr-2" />Chantier *
                </label>
                <div class="relative">
                    <select name="chantier_id" required class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent transition-all outline-hidden appearance-none {{ $errors->has('chantier_id') ? 'border-red-500' : '' }}">
                        <option value="">Sélectionner un chantier</option>
                        @foreach($chantiers as $chantier)
                            <option value="{{ $chantier->id }}" {{ old('chantier_id', $depense->chantier_id) == $chantier->id ? 'selected' : '' }}>{{ $chantier->nom }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                        <x-icon name="chevron-down" class="text-gray-400 size-3" />
                    </div>
                </div>
                @error('chantier_id')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Infos Produit -->
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 font-semibold mb-3">
                        <x-icon name="scan-barcode" class="text-[#D7232A] mr-2" />ID Produit
                    </label>
                    <input type="text" name="id_produit" value="{{ old('id_produit', $depense->id_produit) }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent outline-hidden" placeholder="Ex: SKU-001">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-3">
                        <x-icon name="package" class="text-[#D7232A] mr-2" />Nom du Produit *
                    </label>
                    <input type="text" name="nom_produit" value="{{ old('nom_produit', $depense->nom_produit) }}" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent outline-hidden {{ $errors->has('nom_produit') ? 'border-red-500' : '' }}" placeholder="Ex: Ciment, Acier, etc.">
                    @error('nom_produit')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Catégorie & Quantité -->
            <div class="grid md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-gray-700 font-semibold mb-3">
                        <x-icon name="tag" class="text-[#D7232A] mr-2" />Catégorie *
                    </label>
                    <div class="relative">
                        <select name="categorie_id" required class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent transition-all outline-hidden appearance-none {{ $errors->has('categorie_id') ? 'border-red-500' : '' }}">
                            <option value="">Sélectionner</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('categorie_id', $depense->categorie_id) == $cat->id ? 'selected' : '' }}>{{ $cat->nom }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <x-icon name="chevron-down" class="text-gray-400 size-3" />
                        </div>
                    </div>
                    @error('categorie_id')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-3">
                        <x-icon name="boxes" class="text-[#D7232A] mr-2" />Quantité *
                    </label>
                    <input type="number" name="quantite" value="{{ old('quantite', $depense->quantite) }}" required step="0.01" min="0" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent outline-hidden {{ $errors->has('quantite') ? 'border-red-500' : '' }}" placeholder="0.00">
                    @error('quantite')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-3">
                        <x-icon name="scale" class="text-[#D7232A] mr-2" />Unité *
                    </label>
                    <div class="relative">
                        <select name="unite_id" required class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent transition-all outline-hidden appearance-none {{ $errors->has('unite_id') ? 'border-red-500' : '' }}">
                            <option value="">Sélectionner</option>
                            @foreach($unites as $unite)
                                <option value="{{ $unite->id }}" {{ old('unite_id', $depense->unite_id) == $unite->id ? 'selected' : '' }}>{{ $unite->nom }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <x-icon name="chevron-down" class="text-gray-400 size-3" />
                        </div>
                    </div>
                    @error('unite_id')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Prix -->
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 font-semibold mb-3">
                        <x-icon name="coins" class="text-[#D7232A] mr-2" />Prix Unitaire (F) *
                    </label>
                    <input type="number" name="prix_unitaire" value="{{ old('prix_unitaire', $depense->prix_unitaire) }}" required step="0.01" min="0" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent outline-hidden {{ $errors->has('prix_unitaire') ? 'border-red-500' : '' }}" placeholder="0.00">
                    @error('prix_unitaire')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-3">
                        <x-icon name="receipt" class="text-[#D7232A] mr-2" />Prix Total (F)
                    </label>
                    <input type="text" id="prixTotal" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-100 text-gray-700 font-semibold" disabled placeholder="0.00">
                </div>
            </div>

            <!-- Justificatif (Photo de la facture) -->
            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <x-icon name="file-image" class="text-[#D7232A] mr-2" />Justificatif (Photo de la facture)
                </label>
                
                @if($depense->justificatifs->count() > 0)
                    @php $justif = $depense->justificatifs->first(); @endphp
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl">
                        <p class="text-green-700 font-medium mb-2">
                            <x-icon name="circle-check" class="mr-2" />Justificatif existant : {{ $justif->nom_fichier }}
                        </p>
                        <a href="{{ route('justificatifs.show', $justif) }}" target="_blank" class="text-[#E31E24] hover:underline text-sm">
                            <x-icon name="eye" class="mr-1" />Voir le fichier
                        </a>
                    </div>
                @endif
                
                <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-[#E31E24] transition-colors">
                    <input type="file" name="justificatif" id="justificatif" accept="image/jpeg,image/png,application/pdf" class="hidden" onchange="previewFile(this)">
                    <label for="justificatif" class="cursor-pointer">
                        <div id="filePreview" class="mb-4">
                            <x-icon name="cloud-upload" class="size-12 text-gray-400 mb-3" />
                            <p class="text-gray-600 font-medium">{{ $depense->justificatifs->count() > 0 ? 'Ajouter un justificatif corrigé' : 'Cliquez pour télécharger' }}</p>
                            <p class="text-sm text-gray-400 mt-2">JPG, PNG, PDF (Max 5MB)</p>
                        </div>
                    </label>
                    <div id="fileName" class="text-green-600 font-medium hidden"></div>
                </div>
                @error('justificatif')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Observations -->
            <div>
                <label class="block text-gray-700 font-semibold mb-3">
                    <x-icon name="pencil" class="text-[#D7232A] mr-2" />Observations
                </label>
                <textarea name="observations" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D7232A] focus:border-transparent outline-hidden" rows="4" placeholder="Ajoutez des notes ou observations...">{{ old('observations', $depense->observations) }}</textarea>
            </div>

            <!-- Boutons d'action -->
            <div class="flex gap-4 pt-6 border-t border-gray-200">
                <button type="submit" class="flex-1 px-6 py-3 bg-[#E31E24] text-white font-bold rounded-xl hover:bg-[#C71A20] transition-all flex items-center justify-center space-x-2">
                    <x-icon name="save" class="" />
                    <span>{{ $depense->statut_validation === 'rejetee' ? 'Corriger et soumettre à nouveau' : 'Mettre à jour' }}</span>
                </button>
                <a href="{{ route('depenses.show', $depense->id) }}" class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-300 transition-all flex items-center justify-center space-x-2">
                    <x-icon name="x" class="" />
                    <span>Annuler</span>
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function previewFile(input) {
    const fileName = document.getElementById('fileName');
    const filePreview = document.getElementById('filePreview');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        fileName.textContent = '✓ ' + file.name + ' (' + (file.size / 1024).toFixed(2) + ' KB)';
        fileName.classList.remove('hidden');
        filePreview.classList.add('hidden');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const quantiteInput = document.querySelector('input[name="quantite"]');
    const prixInput = document.querySelector('input[name="prix_unitaire"]');
    const prixTotal = document.getElementById('prixTotal');

    function updateTotal() {
        const quantite = parseFloat(quantiteInput.value) || 0;
        const prix = parseFloat(prixInput.value) || 0;
        const total = quantite * prix;
        prixTotal.value = total.toFixed(2);
    }

    quantiteInput.addEventListener('input', updateTotal);
    prixInput.addEventListener('input', updateTotal);
    updateTotal();
});
</script>
@endsection
