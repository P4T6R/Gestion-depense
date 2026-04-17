@extends('layouts.admin')

@section('title', 'Détails Dépense - StratAction Consulting')
@section('header', 'Détails de la Dépense')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex text-sm text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
        <span class="mx-2">/</span>
        <a href="{{ route('depenses.index') }}" class="hover:text-[#E31E24]">Dépenses</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium">{{ $depense->nom_produit }}</span>
    </nav>

    <!-- En-tête avec actions -->
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $depense->nom_produit }}</h1>
            <p class="text-gray-600 mt-2">
                <i class="fa-solid fa-building text-[#E31E24] mr-2"></i>{{ $depense->chantier->nom }}
            </p>
        </div>
        <div class="flex space-x-2">
            @if(auth()->user()->role === 'chef_chantier' && $depense->user_id === auth()->id() && $depense->statut_validation === 'en_attente')
            <a href="{{ route('depenses.edit', $depense->id) }}" class="px-6 py-3 bg-orange-600 text-white font-bold rounded-xl shadow-lg hover:bg-orange-700 transition-all flex items-center space-x-2">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Modifier</span>
            </a>
            <form action="{{ route('depenses.destroy', $depense->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-6 py-3 bg-red-600 text-white font-bold rounded-xl shadow-lg hover:bg-red-700 transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-trash"></i>
                    <span>Supprimer</span>
                </button>
            </form>
            @endif

            @if(auth()->user()->role === 'directeur' && $depense->statut_validation === 'en_attente')
            <form action="{{ route('depenses.approve', $depense->id) }}" method="POST">
                @csrf
                <button type="submit" class="px-6 py-3 bg-green-600 text-white font-bold rounded-xl shadow-lg hover:bg-green-700 transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-check"></i>
                    <span>Approuver</span>
                </button>
            </form>
            @endif
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        <!-- Informations principales -->
        <div class="md:col-span-2 space-y-6">
            <!-- Détails du produit -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fa-solid fa-box text-[#E31E24] mr-3"></i>
                    Détails du Produit
                </h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-gray-600 text-sm">Quantité</p>
                        <p class="text-gray-900 font-semibold text-lg">{{ number_format($depense->quantite, 2, ',', ' ') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Unité de Mesure</p>
                        <p class="text-gray-900 font-semibold text-lg">{{ $depense->unite->nom ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Prix Unitaire</p>
                        <p class="text-gray-900 font-semibold text-lg">{{ number_format($depense->prix_unitaire, 0, ',', ' ') }} F</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Prix Total</p>
                        <p class="text-[#E31E24] font-bold text-lg">{{ number_format($depense->prix_total, 0, ',', ' ') }} F</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-gray-600 text-sm">Catégorie</p>
                        <p class="text-gray-900 font-semibold">{{ $depense->categorie->nom ?? 'N/A' }}</p>
                    </div>
                    
                    <!-- Justificatifs - Galerie avec miniatures -->
                    @if($depense->justificatifs->count() > 0)
                    <div class="md:col-span-2" x-data="{
                        isOpen: false,
                        currentIndex: 0,
                        currentUrl: '',
                        currentFileName: '',
                        isImage: false,
                        zoomLevel: 1,
                        rotation: 0,
                        justificatifs: [
                            @foreach($depense->justificatifs as $j)
                            {
                                url: '{{ addslashes(asset("storage/" . $j->chemin_fichier)) }}',
                                name: '{{ addslashes($j->nom_fichier) }}',
                                isImage: {{ str_contains($j->type_fichier, 'image') ? 'true' : 'false' }}
                            }{{ !$loop->last ? ',' : '' }}
                            @endforeach
                        ],
                        openViewer(index, url, fileName, isImage) {
                            this.currentIndex = index;
                            this.currentUrl = url;
                            this.currentFileName = fileName;
                            this.isImage = isImage;
                            this.zoomLevel = 1;
                            this.rotation = 0;
                            this.isOpen = true;
                            document.body.style.overflow = 'hidden';
                        },
                        closeViewer() {
                            this.isOpen = false;
                            document.body.style.overflow = 'auto';
                        },
                        nextImage() {
                            if (this.justificatifs.length > 0) {
                                this.currentIndex = (this.currentIndex + 1) % this.justificatifs.length;
                                this.loadImage();
                            }
                        },
                        prevImage() {
                            if (this.justificatifs.length > 0) {
                                this.currentIndex = (this.currentIndex - 1 + this.justificatifs.length) % this.justificatifs.length;
                                this.loadImage();
                            }
                        },
                        loadImage() {
                            const current = this.justificatifs[this.currentIndex];
                            this.currentUrl = current.url;
                            this.currentFileName = current.name;
                            this.isImage = current.isImage;
                            this.resetView();
                        },
                        zoomIn() {
                            if (this.zoomLevel < 3) {
                                this.zoomLevel = Math.min(3, this.zoomLevel + 0.25);
                            }
                        },
                        zoomOut() {
                            if (this.zoomLevel > 0.25) {
                                this.zoomLevel = Math.max(0.25, this.zoomLevel - 0.25);
                            }
                        },
                        rotateLeft() {
                            this.rotation = (this.rotation - 90) % 360;
                        },
                        rotateRight() {
                            this.rotation = (this.rotation + 90) % 360;
                        },
                        resetView() {
                            this.zoomLevel = 1;
                            this.rotation = 0;
                        }
                    }">
                        <p class="text-gray-600 text-sm mb-3">
                            <i class="fa-solid fa-images text-teal-500 mr-2"></i>
                            Justificatifs ({{ $depense->justificatifs->count() }})
                        </p>
                        
                        <!-- Galerie de miniatures -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($depense->justificatifs as $index => $justif)
                            @php
                                $isImage = str_contains($justif->type_fichier, 'image');
                                $isPdf = str_contains($justif->type_fichier, 'pdf');
                            @endphp
                            <div class="group relative cursor-pointer" 
                                 @click="openViewer({{ $index }}, '{{ asset('storage/' . $justif->chemin_fichier) }}', '{{ $justif->nom_fichier }}', {{ $isImage ? 'true' : 'false' }})">
                                <!-- Miniature -->
                                <div class="aspect-square bg-gray-100 rounded-xl overflow-hidden border-2 border-gray-200 group-hover:border-teal-500 transition-all shadow-md group-hover:shadow-xl">
                                    @if($isImage)
                                        <img src="{{ asset('storage/' . $justif->chemin_fichier) }}" 
                                             alt="{{ $justif->nom_fichier }}"
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                    @else
                                        <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-red-50 to-red-100">
                                            <i class="fa-solid fa-file-pdf text-5xl text-red-500 mb-2"></i>
                                            <span class="text-xs text-gray-600 px-2 text-center truncate w-full">{{ Str::limit($justif->nom_fichier, 15) }}</span>
                                        </div>
                                    @endif
                                    <!-- Overlay au survol -->
                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <i class="fa-solid fa-magnifying-glass-plus text-white text-2xl"></i>
                                    </div>
                                </div>
                                <!-- Taille du fichier -->
                                <p class="text-xs text-gray-500 mt-1 text-center">{{ number_format($justif->taille_fichier / 1024, 1) }} KB</p>
                            </div>
                            @endforeach
                        </div>

                        <!-- Modal Visualisation Plein Écran -->
                        <div x-show="isOpen" 
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center"
                             @keydown.escape.window="closeViewer()"
                             x-cloak>
                            
                            <!-- Barre d'outils supérieure -->
                            <div class="absolute top-0 left-0 right-0 bg-gradient-to-b from-black/80 to-transparent p-4 flex items-center justify-between z-10">
                                <div class="text-white">
                                    <h3 class="font-bold text-lg" x-text="currentFileName"></h3>
                                    <p class="text-sm text-gray-300">Justificatif <span x-text="currentIndex + 1"></span> / {{ $depense->justificatifs->count() }}</p>
                                </div>
                                <button @click="closeViewer()" class="w-10 h-10 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center text-white transition">
                                    <i class="fa-solid fa-times text-xl"></i>
                                </button>
                            </div>

                            <!-- Navigation gauche -->
                            @if($depense->justificatifs->count() > 1)
                            <button @click="prevImage()" 
                                    class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center text-white transition z-10">
                                <i class="fa-solid fa-chevron-left text-xl"></i>
                            </button>
                            @endif

                            <!-- Zone d'affichage de l'image -->
                            <div class="relative max-w-[90vw] max-h-[80vh] overflow-auto" id="imageContainer">
                                <template x-if="isImage">
                                    <img :src="currentUrl" 
                                         :alt="currentFileName"
                                         :style="`transform: scale(${zoomLevel}) rotate(${rotation}deg); transition: transform 0.3s ease;`"
                                         class="max-w-full max-h-[80vh] object-contain mx-auto">
                                </template>
                                <template x-if="!isImage">
                                    <div class="bg-white rounded-xl p-12 text-center">
                                        <i class="fa-solid fa-file-pdf text-8xl text-red-500 mb-6"></i>
                                        <p class="text-gray-900 font-bold text-xl mb-4" x-text="currentFileName"></p>
                                        <p class="text-gray-600 mb-6">Ce fichier PDF ne peut pas être prévisualisé directement.</p>
                                        <div class="flex gap-4 justify-center">
                                            <a :href="currentUrl" target="_blank" class="px-6 py-3 bg-[#E31E24] text-white rounded-xl hover:bg-[#C71A20] transition flex items-center space-x-2">
                                                <i class="fa-solid fa-external-link"></i>
                                                <span>Ouvrir dans un nouvel onglet</span>
                                            </a>
                                            <a :href="currentUrl" download class="px-6 py-3 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition flex items-center space-x-2">
                                                <i class="fa-solid fa-download"></i>
                                                <span>Télécharger</span>
                                            </a>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Navigation droite -->
                            @if($depense->justificatifs->count() > 1)
                            <button @click="nextImage()" 
                                    class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center text-white transition z-10">
                                <i class="fa-solid fa-chevron-right text-xl"></i>
                            </button>
                            @endif

                            <!-- Barre d'outils inférieure -->
                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6 z-10">
                                <div class="flex items-center justify-center space-x-4">
                                    <!-- Zoom arrière -->
                                    <button @click="zoomOut()" 
                                            class="w-12 h-12 bg-white/20 hover:bg-white/30 rounded-xl flex items-center justify-center text-white transition"
                                            title="Zoom arrière">
                                        <i class="fa-solid fa-magnifying-glass-minus text-lg"></i>
                                    </button>
                                    
                                    <!-- Indicateur de zoom -->
                                    <span class="text-white font-mono bg-black/50 px-3 py-1 rounded-lg" x-text="Math.round(zoomLevel * 100) + '%'"></span>
                                    
                                    <!-- Zoom avant -->
                                    <button @click="zoomIn()" 
                                            class="w-12 h-12 bg-white/20 hover:bg-white/30 rounded-xl flex items-center justify-center text-white transition"
                                            title="Zoom avant">
                                        <i class="fa-solid fa-magnifying-glass-plus text-lg"></i>
                                    </button>

                                    <div class="w-px h-8 bg-white/30"></div>

                                    <!-- Rotation gauche -->
                                    <button @click="rotateLeft()" 
                                            class="w-12 h-12 bg-white/20 hover:bg-white/30 rounded-xl flex items-center justify-center text-white transition"
                                            title="Rotation gauche">
                                        <i class="fa-solid fa-rotate-left text-lg"></i>
                                    </button>
                                    
                                    <!-- Rotation droite -->
                                    <button @click="rotateRight()" 
                                            class="w-12 h-12 bg-white/20 hover:bg-white/30 rounded-xl flex items-center justify-center text-white transition"
                                            title="Rotation droite">
                                        <i class="fa-solid fa-rotate-right text-lg"></i>
                                    </button>
                                    
                                    <!-- Réinitialiser -->
                                    <button @click="resetView()" 
                                            class="w-12 h-12 bg-white/20 hover:bg-white/30 rounded-xl flex items-center justify-center text-white transition"
                                            title="Réinitialiser la vue">
                                        <i class="fa-solid fa-arrows-rotate text-lg"></i>
                                    </button>

                                    <div class="w-px h-8 bg-white/30"></div>

                                    <!-- Télécharger -->
                                    <a :href="currentUrl" download 
                                       class="w-12 h-12 bg-teal-600 hover:bg-teal-700 rounded-xl flex items-center justify-center text-white transition"
                                       title="Télécharger">
                                        <i class="fa-solid fa-download text-lg"></i>
                                    </a>
                                    
                                    <!-- Ouvrir dans nouvel onglet -->
                                    <a :href="currentUrl" target="_blank" 
                                       class="w-12 h-12 bg-[#E31E24] hover:bg-[#C71A20] rounded-xl flex items-center justify-center text-white transition"
                                       title="Ouvrir dans un nouvel onglet">
                                        <i class="fa-solid fa-external-link text-lg"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    @if($depense->observations)
                    <div class="md:col-span-2">
                        <p class="text-gray-600 text-sm">Observations</p>
                        <p class="text-gray-900 mt-2">{{ $depense->observations }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Statut de validation -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fa-solid fa-tasks text-green-500 mr-3"></i>
                    Statut de Validation
                </h2>
                @php
                    $statusInfo = [
                        'en_attente' => ['color' => 'yellow', 'icon' => 'hourglass-half', 'label' => 'En attente'],
                        'acceptee' => ['color' => 'blue', 'icon' => 'check-circle', 'label' => 'Acceptée'],
                        'validee' => ['color' => 'green', 'icon' => 'stamp', 'label' => 'Validée'],
                        'rejetee' => ['color' => 'red', 'icon' => 'ban', 'label' => 'Rejetée'],
                    ];
                    $info = $statusInfo[$depense->statut_validation] ?? ['color' => 'gray', 'icon' => 'question', 'label' => 'Inconnu'];
                @endphp
                <div class="flex items-center space-x-4 mb-6">
                    <i class="fa-solid fa-{{ $info['icon'] }} text-4xl text-{{ $info['color'] }}-500"></i>
                    <div>
                        <p class="text-gray-600 text-sm">Statut</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $info['label'] }}</p>
                    </div>
                </div>

                @if($depense->statut_validation === 'validee')
                <div class="border-t border-gray-200 pt-4 space-y-2">
                    <div>
                        <p class="text-gray-600 text-sm">Validée par</p>
                        <p class="text-gray-900 font-semibold">{{ $depense->validateur->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Date de Validation</p>
                        <p class="text-gray-900 font-semibold">{{ $depense->valide_le?->format('d/m/Y H:i') ?? 'N/A' }}</p>
                    </div>
                </div>
                @endif

                @if($depense->statut_validation === 'rejetee' && $depense->motif_rejet)
                <div class="border-t border-gray-200 pt-4">
                    <p class="text-gray-600 text-sm">Motif du Rejet</p>
                    <p class="text-red-600 font-semibold mt-2">{{ $depense->motif_rejet }}</p>
                </div>
                @endif
            </div>

            @if(auth()->user()->role === 'comptable')
                @if($depense->statut_validation === 'acceptee')
                <!-- Validation Comptable -->
                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fa-solid fa-stamp text-green-500 mr-3"></i>
                        Validation Comptable
                    </h2>
                    <form action="{{ route('depenses.validate', $depense->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="grid md:grid-cols-2 gap-4">
                            <button type="submit" name="action" value="accept" class="px-6 py-3 bg-green-600 text-white font-bold rounded-xl hover:bg-green-700 transition-all flex items-center justify-center space-x-2">
                                <i class="fa-solid fa-check"></i>
                                <span>Valider</span>
                            </button>
                            <button type="button" onclick="toggleRejectForm()" class="px-6 py-3 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700 transition-all flex items-center justify-center space-x-2">
                                <i class="fa-solid fa-times"></i>
                                <span>Rejeter</span>
                            </button>
                        </div>

                        <div id="rejectForm" style="display: none;" class="border-t border-gray-200 pt-4 mt-4">
                            <label class="block text-gray-700 font-semibold mb-2">Motif du rejet</label>
                            <textarea id="motif_rejet" name="motif_rejet" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none" rows="4" placeholder="Expliquez le motif du rejet..."></textarea>
                            <button type="submit" name="action" value="reject" class="mt-4 w-full px-6 py-3 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700 transition-all flex items-center justify-center space-x-2">
                                <i class="fa-solid fa-paper-plane"></i>
                                <span>Soumettre le Rejet</span>
                            </button>
                        </div>
                    </form>
                </div>
                @elseif($depense->statut_validation === 'en_attente')
                <div class="bg-blue-50 border-l-4 border-[#E31E24] p-6 rounded-xl shadow-sm">
                    <div class="flex items-center">
                        <i class="fa-solid fa-circle-info text-[#E31E24] text-2xl mr-4"></i>
                        <div>
                            <p class="text-blue-800 font-bold">En attente d'approbation</p>
                            <p class="text-[#E31E24] text-sm">Cette dépense doit être approuvée par le directeur avant que vous ne puissiez la valider.</p>
                        </div>
                    </div>
                </div>
                @endif
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Créé par -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fa-solid fa-user text-[#E31E24] mr-3"></i>
                    Créé par
                </h2>
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-[#E31E24] to-[#C71A20] rounded-full flex items-center justify-center text-white font-bold">
                        {{ substr($depense->utilisateur->name, 0, 1) }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">{{ $depense->utilisateur->name }}</p>
                        <p class="text-sm text-gray-600">{{ $depense->utilisateur->role }}</p>
                    </div>
                </div>
            </div>

            <!-- Dates -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fa-solid fa-calendar text-orange-500 mr-3"></i>
                    Dates
                </h2>
                <div class="space-y-4">
                    <div>
                        <p class="text-gray-600 text-sm">Créée le</p>
                        <p class="text-gray-900 font-semibold">{{ $depense->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @if($depense->valide_le)
                    <div>
                        <p class="text-gray-600 text-sm">Validée le</p>
                        <p class="text-gray-900 font-semibold">{{ $depense->valide_le->format('d/m/Y H:i') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleRejectForm() {
    const form = document.getElementById('rejectForm');
    const textarea = document.getElementById('motif_rejet');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    
    if (form.style.display === 'block') {
        textarea.setAttribute('required', '');
    } else {
        textarea.removeAttribute('required');
    }
}
</script>
@endsection
