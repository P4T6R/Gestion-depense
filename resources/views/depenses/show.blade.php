@extends('layouts.admin')

@section('title', 'Détails Dépense - StratAction Consulting')
@section('header', 'Détails de la Dépense')

@section('content')
<div class="space-y-6" x-data="{ directorRejectOpen: {{ $errors->has('motif_rejet') ? 'true' : 'false' }} }">
    @if(session('success'))
        <div role="status" class="flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            <x-icon name="circle-check" class="mt-0.5 size-5" />
            <span>{{ session('success') }}</span>
        </div>
    @endif
    <!-- Breadcrumb -->
    <nav class="flex text-sm text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
        <span class="mx-2">/</span>
        <a href="{{ route('depenses.index') }}" class="hover:text-[#E31E24]">Dépenses</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium">{{ $depense->libelle }}</span>
    </nav>

    <!-- En-tête avec actions -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl">{{ $depense->libelle }}</h1>
            <p class="text-gray-600 mt-2">
                <x-icon name="building-2" class="text-[#D7232A] mr-2" />{{ $depense->chantier?->nom ?? 'Chantier archivé' }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('update', $depense)
            <a href="{{ route('depenses.edit', $depense->id) }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                <x-icon name="square-pen" class="" />
                <span>{{ $depense->statut_validation === 'rejetee' ? 'Corriger et soumettre à nouveau' : 'Modifier' }}</span>
            </a>
            @endcan
            @can('delete', $depense)
            <form action="{{ route('depenses.destroy', $depense->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">
                    <x-icon name="trash-2" class="" />
                    <span>Supprimer</span>
                </button>
            </form>
            @endcan

            @if(auth()->user()->role === 'directeur' && $depense->statut_validation === 'en_attente')
            <form action="{{ route('depenses.approve', $depense->id) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-[#D7232A] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#B91C23]">
                    <x-icon name="check" class="" />
                    <span>Approuver</span>
                </button>
            </form>
            <button type="button" @click="directorRejectOpen = !directorRejectOpen" :aria-expanded="directorRejectOpen" aria-controls="director-reject-form" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">
                <x-icon name="x" class="size-4" />
                <span>Rejeter</span>
            </button>
            @endif
        </div>
    </div>

    @if(auth()->user()->role === 'directeur' && $depense->statut_validation === 'en_attente')
    <section id="director-reject-form" x-show="directorRejectOpen" x-cloak class="rounded-2xl border border-red-200 bg-white p-5 sm:p-6" aria-label="Rejeter la facture">
        <h2 class="text-lg font-semibold text-gray-900">Motif du rejet</h2>
        <p class="mt-1 text-sm text-gray-600">Le motif sera visible sur la fiche. La facture ne pourra pas être validée par la comptable.</p>
        <form action="{{ route('depenses.reject', $depense) }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="director-motif-rejet" class="mb-2 block text-sm font-semibold text-gray-700">Expliquez votre décision *</label>
                <textarea id="director-motif-rejet" name="motif_rejet" rows="3" required minlength="10" maxlength="10000" aria-describedby="director-motif-help @error('motif_rejet') director-motif-error @enderror" class="w-full rounded-xl border border-gray-200 px-4 py-3 outline-hidden focus:border-[#D7232A] focus:ring-2 focus:ring-[#D7232A]" placeholder="Précisez ce qui doit être corrigé…">{{ old('motif_rejet') }}</textarea>
                <p id="director-motif-help" class="mt-1 text-xs text-gray-500">Au moins 10 caractères.</p>
                @error('motif_rejet')
                    <p id="director-motif-error" role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-[#D7232A] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#B91C23]"><x-icon name="x" class="size-4" />Confirmer le rejet</button>
                <button type="button" @click="directorRejectOpen = false" class="min-h-11 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Annuler</button>
            </div>
        </form>
    </section>
    @endif

    <div class="grid min-w-0 gap-6 md:grid-cols-3">
        <!-- Informations principales -->
        <div class="min-w-0 space-y-6 md:col-span-2">
            @if($depense->lignes->isNotEmpty())
            <section class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-8" aria-label="Articles de la facture">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Articles de la facture</h2>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-left text-sm">
                        <caption class="sr-only">Détail des articles et des montants de la facture</caption>
                        <thead class="border-b border-gray-200 text-gray-500"><tr><th scope="col" class="pb-3 font-medium">Désignation</th><th scope="col" class="pb-3 font-medium">Quantité</th><th scope="col" class="pb-3 font-medium">Prix unitaire</th><th scope="col" class="pb-3 font-medium">Montant</th></tr></thead>
                        <tbody>@foreach($depense->lignes as $ligne)
                            <tr><td class="py-2">{{ $ligne->designation }}</td><td>{{ $ligne->quantite }} {{ $ligne->uniteMesure?->symbole }}</td><td>{{ number_format($ligne->prix_unitaire, 2, ',', ' ') }} F</td><td>{{ number_format($ligne->montant, 2, ',', ' ') }} F</td></tr>
                        @endforeach</tbody>
                    </table>
                </div>
                <p class="mt-4 border-t border-gray-200 pt-4 text-right font-semibold">Total : {{ number_format($depense->prix_total, 2, ',', ' ') }} F</p>
            </section>
            @endif
            <!-- Détails du produit -->
            @if($depense->lignes->isEmpty() || $depense->justificatifs->isNotEmpty() || $depense->observations)
            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <x-icon name="package" class="text-[#D7232A] mr-3" />
                    {{ $depense->lignes->isNotEmpty() ? 'Informations complémentaires' : 'Détails du produit' }}
                </h2>
                <div class="grid md:grid-cols-2 gap-6">
                    @if($depense->lignes->isEmpty())
                    <div>
                        <p class="text-gray-600 text-sm">Quantité</p>
                        <p class="text-gray-900 font-semibold text-lg">{{ number_format($depense->quantite ?? 0, 2, ',', ' ') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Unité de Mesure</p>
                        <p class="text-gray-900 font-semibold text-lg">{{ $depense->unite->nom ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Prix Unitaire</p>
                        <p class="text-gray-900 font-semibold text-lg">{{ number_format($depense->prix_unitaire ?? 0, 0, ',', ' ') }} F</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Prix Total</p>
                        <p class="text-[#E31E24] font-bold text-lg">{{ number_format($depense->prix_total, 0, ',', ' ') }} F</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-gray-600 text-sm">Catégorie</p>
                        <p class="text-gray-900 font-semibold">{{ $depense->categorie->nom ?? 'N/A' }}</p>
                    </div>
                    @endif

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
                        pdfDocument: null,
                        pdfPage: 1,
                        pdfPages: 0,
                        pdfLoading: false,
                        pdfError: false,
                        pdfRequestId: 0,
                        justificatifs: {{ Illuminate\Support\Js::from($depense->justificatifs->map(fn ($j) => [
                            'url' => route('justificatifs.show', $j),
                            'name' => $j->nom_fichier,
                            'isImage' => str_starts_with($j->type_fichier, 'image/'),
                        ])->values()) }},
                        openViewer(index) {
                            this.currentIndex = index;
                            this.loadImage();
                            this.isOpen = true;
                            if (!this.isImage) this.$nextTick(() => this.showPdf());
                        },
                        closeViewer() {
                            this.isOpen = false;
                            this.pdfRequestId++;
                            if (this.pdfDocument) Alpine.raw(this.pdfDocument).destroy();
                            this.pdfDocument = null;
                        },
                        nextImage() {
                            if (this.justificatifs.length > 0) {
                                this.currentIndex = (this.currentIndex + 1) % this.justificatifs.length;
                                this.loadImage();
                                if (!this.isImage) this.$nextTick(() => this.showPdf());
                            }
                        },
                        prevImage() {
                            if (this.justificatifs.length > 0) {
                                this.currentIndex = (this.currentIndex - 1 + this.justificatifs.length) % this.justificatifs.length;
                                this.loadImage();
                                if (!this.isImage) this.$nextTick(() => this.showPdf());
                            }
                        },
                        loadImage() {
                            const current = this.justificatifs[this.currentIndex];
                            this.currentUrl = current.url;
                            this.currentFileName = current.name;
                            this.isImage = current.isImage;
                            this.resetView();
                            this.pdfRequestId++;
                            if (this.pdfDocument) Alpine.raw(this.pdfDocument).destroy();
                            this.pdfDocument = null;
                            this.pdfPages = 0;
                            this.pdfPage = 1;
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
                        },
                        async showPdf() {
                            const requestId = ++this.pdfRequestId;
                            this.pdfLoading = true;
                            this.pdfError = false;
                            try {
                                const preview = await window.loadPdfPreview();
                                const document = await preview.openPdf(this.currentUrl);
                                if (!this.isOpen || this.isImage || requestId !== this.pdfRequestId) {
                                    document.destroy();
                                    return;
                                }
                                this.pdfDocument = document;
                                this.pdfPages = document.numPages;
                                await this.renderPdfPage(preview);
                            } catch (error) {
                                if (requestId === this.pdfRequestId) this.pdfError = true;
                            } finally {
                                if (requestId === this.pdfRequestId) this.pdfLoading = false;
                            }
                        },
                        async renderPdfPage(preview = null) {
                            if (!this.pdfDocument) return;
                            this.pdfLoading = true;
                            try {
                                preview ??= await window.loadPdfPreview();
                                await new Promise(resolve => this.$nextTick(resolve));
                                await preview.renderPdfPage(Alpine.raw(this.pdfDocument), this.pdfPage, this.$refs.pdfCanvas);
                                this.pdfError = false;
                            } catch (error) {
                                this.pdfError = true;
                            } finally {
                                this.pdfLoading = false;
                            }
                        },
                        async changePdfPage(delta) {
                            if (this.pdfLoading || !this.pdfDocument) return;
                            const next = this.pdfPage + delta;
                            if (next < 1 || next > this.pdfPages) return;
                            this.pdfPage = next;
                            await this.renderPdfPage();
                        }
                    }">
                        <p class="text-gray-600 text-sm mb-3">
                            <x-icon name="images" class="text-[#D7232A] mr-2" />
                            Justificatifs ({{ $depense->justificatifs->count() }})
                        </p>

                        <!-- Galerie de miniatures -->
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach($depense->justificatifs as $index => $justif)
                            @php
                                $isImage = str_contains($justif->type_fichier, 'image');
                            @endphp
                            <button type="button" class="group min-w-0 text-left" aria-label="Prévisualiser {{ $justif->nom_fichier }}" @click="openViewer({{ $index }})">
                                <!-- Miniature -->
                                <div class="relative aspect-square overflow-hidden rounded-lg border border-gray-200 bg-gray-50 transition-colors group-hover:border-[#D7232A] group-focus-visible:outline-2 group-focus-visible:outline-offset-2 group-focus-visible:outline-[#D7232A]">
                                    @if($isImage)
                                        <img src="{{ route('justificatifs.show', $justif) }}"
                                             alt="{{ $justif->nom_fichier }}"
                                             class="h-full w-full object-cover">
                                    @else
                                        <div class="flex h-full w-full flex-col items-center justify-center bg-[#F7F6F3]">
                                            <x-icon name="file-text" class="mb-2 size-9 text-[#D7232A]" />
                                            <span class="text-xs text-gray-600 px-2 text-center truncate w-full">{{ Str::limit($justif->nom_fichier, 15) }}</span>
                                        </div>
                                    @endif
                                    <!-- Overlay au survol -->
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100">
                                        <x-icon name="eye" class="size-6 text-white" />
                                    </div>
                                </div>
                                <!-- Taille du fichier -->
                                <span class="mt-1 block truncate text-center text-xs text-gray-500">{{ $justif->nom_fichier }} · {{ number_format($justif->taille_fichier / 1024, 1) }} Ko</span>
                            </button>
                            @endforeach
                        </div>

                        <!-- Modal Visualisation Plein Écran -->
                        <div x-show="isOpen" role="dialog" aria-modal="true" aria-label="Aperçu du justificatif" x-trap.inert.noscroll="isOpen"
                             class="fixed inset-0 z-50 flex flex-col bg-[#161616] text-white"
                             @keydown.escape.window="closeViewer()"
                             @keydown.arrow-right.window="if (isOpen) nextImage()"
                             @keydown.arrow-left.window="if (isOpen) prevImage()"
                             x-cloak>
                            <div class="flex min-h-16 items-center gap-3 border-b border-white/15 px-3 sm:px-6">
                                <div class="min-w-0 flex-1">
                                    <h3 class="truncate text-sm font-semibold sm:text-base" x-text="currentFileName"></h3>
                                    <p class="text-xs text-white/65">Justificatif <span x-text="currentIndex + 1"></span> / {{ $depense->justificatifs->count() }}</p>
                                </div>
                                <a :href="currentUrl" target="_blank" rel="noopener noreferrer" class="inline-flex size-10 items-center justify-center rounded-lg text-white/80 hover:bg-white/10 hover:text-white" aria-label="Ouvrir dans un nouvel onglet" title="Ouvrir dans un nouvel onglet"><x-icon name="external-link" class="size-5" /></a>
                                <a :href="currentUrl" download class="inline-flex size-10 items-center justify-center rounded-lg text-white/80 hover:bg-white/10 hover:text-white" aria-label="Télécharger le justificatif" title="Télécharger"><x-icon name="download" class="size-5" /></a>
                                <button type="button" @click="closeViewer()" class="inline-flex size-10 items-center justify-center rounded-lg text-white/80 hover:bg-white/10 hover:text-white" aria-label="Fermer l’aperçu"><x-icon name="x" class="size-5" /></button>
                            </div>

                            <div class="relative flex min-h-0 flex-1 items-center justify-center p-3 sm:px-16 sm:py-5">
                                @if($depense->justificatifs->count() > 1)
                                <button type="button" @click="prevImage()" class="absolute left-2 z-10 flex size-10 items-center justify-center rounded-full bg-[#303030] hover:bg-[#454545] sm:left-4" aria-label="Justificatif précédent"><x-icon name="chevron-left" class="size-5" /></button>
                                @endif

                                <template x-if="isImage">
                                    <div class="flex h-full w-full items-center justify-center overflow-auto">
                                        <img :src="currentUrl" :alt="currentFileName"
                                             :style="`transform: scale(${zoomLevel}) rotate(${rotation}deg)`"
                                             class="max-h-full max-w-full object-contain transition-transform duration-200">
                                    </div>
                                </template>
                                <template x-if="!isImage">
                                    <div class="flex h-full w-full max-w-5xl flex-col items-center">
                                        <div class="min-h-0 w-full flex-1 overflow-auto rounded-md bg-[#303030] text-center">
                                            <canvas x-ref="pdfCanvas" class="mx-auto bg-white shadow-lg" aria-label="Page PDF prévisualisée"></canvas>
                                            <p x-show="pdfLoading" role="status" class="py-6 text-sm text-white/70">Chargement du document…</p>
                                            <p x-show="pdfError" role="alert" class="p-6 text-sm">L’aperçu est indisponible. Utilisez « Ouvrir » ou « Télécharger ».</p>
                                        </div>
                                    </div>
                                </template>

                                @if($depense->justificatifs->count() > 1)
                                <button type="button" @click="nextImage()" class="absolute right-2 z-10 flex size-10 items-center justify-center rounded-full bg-[#303030] hover:bg-[#454545] sm:right-4" aria-label="Justificatif suivant"><x-icon name="chevron-right" class="size-5" /></button>
                                @endif
                            </div>

                            <div class="flex min-h-16 items-center justify-center gap-2 border-t border-white/15 px-2" x-show="!isImage">
                                <button type="button" @click="changePdfPage(-1)" :disabled="pdfLoading || pdfPage <= 1" class="flex size-10 items-center justify-center rounded-lg hover:bg-white/10 disabled:opacity-40" aria-label="Page précédente"><x-icon name="chevron-left" class="size-5" /></button>
                                <span class="min-w-18 text-center text-sm tabular-nums"><span x-text="pdfPage"></span> / <span x-text="pdfPages"></span></span>
                                <button type="button" @click="changePdfPage(1)" :disabled="pdfLoading || pdfPage >= pdfPages" class="flex size-10 items-center justify-center rounded-lg hover:bg-white/10 disabled:opacity-40" aria-label="Page suivante"><x-icon name="chevron-right" class="size-5" /></button>
                            </div>
                            <div class="flex min-h-16 items-center justify-center gap-1 border-t border-white/15 px-2" x-show="isImage">
                                <button type="button" @click="zoomOut()" class="flex size-10 items-center justify-center rounded-lg hover:bg-white/10" aria-label="Zoom arrière"><x-icon name="zoom-out" class="size-5" /></button>
                                <span class="w-14 text-center text-xs tabular-nums" x-text="Math.round(zoomLevel * 100) + '%'"></span>
                                <button type="button" @click="zoomIn()" class="flex size-10 items-center justify-center rounded-lg hover:bg-white/10" aria-label="Zoom avant"><x-icon name="zoom-in" class="size-5" /></button>
                                <span class="mx-1 h-6 w-px bg-white/20"></span>
                                <button type="button" @click="rotateLeft()" class="flex size-10 items-center justify-center rounded-lg hover:bg-white/10" aria-label="Tourner vers la gauche"><x-icon name="rotate-ccw" class="size-5" /></button>
                                <button type="button" @click="rotateRight()" class="flex size-10 items-center justify-center rounded-lg hover:bg-white/10" aria-label="Tourner vers la droite"><x-icon name="rotate-cw" class="size-5" /></button>
                                <button type="button" @click="resetView()" class="flex size-10 items-center justify-center rounded-lg hover:bg-white/10" aria-label="Réinitialiser la vue"><x-icon name="refresh-cw" class="size-5" /></button>
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
            @endif

            <!-- Statut de validation -->
            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <x-icon name="list-todo" class="text-[#D7232A] mr-3" />
                    Statut de Validation
                </h2>
                @php
                    $statusInfo = [
                        'en_attente' => ['icon' => 'hourglass', 'label' => 'En attente'],
                        'acceptee' => ['icon' => 'circle-check', 'label' => 'Acceptée'],
                        'validee' => ['icon' => 'stamp', 'label' => 'Validée'],
                        'rejetee' => ['icon' => 'ban', 'label' => 'Rejetée'],
                    ];
                    $info = $statusInfo[$depense->statut_validation] ?? ['icon' => 'circle-help', 'label' => 'Inconnu'];
                @endphp
                <div class="flex items-center space-x-4 mb-6">
                    <x-icon :name="$info['icon']" class="size-10 text-[#D7232A]" />
                    <div>
                        <p class="text-gray-600 text-sm">Statut</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $info['label'] }}</p>
                    </div>
                </div>

                @if($depense->statut_validation === 'acceptee')
                    <p class="rounded-xl bg-[#F7F6F3] p-4 text-sm text-gray-700">Approuvée par le directeur. La validation comptable reste nécessaire avant toute consommation du budget ou paiement.</p>
                @endif

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
                    @can('update', $depense)
                    <p class="mt-3 text-sm text-gray-600">Corrigez la facture et enregistrez-la pour la soumettre de nouveau au directeur.</p>
                    @endcan
                </div>
                @endif
            </div>

            @if(auth()->user()->role === 'comptable')
                @if($depense->statut_validation === 'acceptee')
                <!-- Validation Comptable -->
                <div class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <x-icon name="stamp" class="text-[#D7232A] mr-3" />
                        Validation Comptable
                    </h2>
                    <form action="{{ route('depenses.validate', $depense->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="grid md:grid-cols-2 gap-4">
                            <button type="submit" name="action" value="accept" class="px-6 py-3 bg-green-600 text-white font-bold rounded-xl hover:bg-green-700 transition-all flex items-center justify-center space-x-2">
                                <x-icon name="check" class="" />
                                <span>Valider</span>
                            </button>
                            <button type="button" onclick="toggleRejectForm()" class="px-6 py-3 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700 transition-all flex items-center justify-center space-x-2">
                                <x-icon name="x" class="" />
                                <span>Rejeter</span>
                            </button>
                        </div>

                        <div id="rejectForm" style="display: none;" class="border-t border-gray-200 pt-4 mt-4">
                            <label class="block text-gray-700 font-semibold mb-2">Motif du rejet</label>
                            <textarea id="motif_rejet" name="motif_rejet" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-hidden" rows="4" placeholder="Expliquez le motif du rejet..."></textarea>
                            <button type="submit" name="action" value="reject" class="mt-4 w-full px-6 py-3 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700 transition-all flex items-center justify-center space-x-2">
                                <x-icon name="send" class="" />
                                <span>Soumettre le Rejet</span>
                            </button>
                        </div>
                    </form>
                </div>
                @elseif($depense->statut_validation === 'en_attente')
                <div class="rounded-xl border border-[#E7E5E1] bg-white p-5">
                    <div class="flex items-center">
                        <x-icon name="info" class="text-[#D7232A] size-6 mr-4" />
                        <div>
                            <p class="font-semibold text-gray-900">En attente d'approbation</p>
                            <p class="text-sm text-gray-600">Cette dépense doit être approuvée par le directeur avant que vous ne puissiez la valider.</p>
                        </div>
                    </div>
                </div>
                @endif
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Créé par -->
            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <x-icon name="user-round" class="text-[#D7232A] mr-3" />
                    Créé par
                </h2>
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-linear-to-br from-[#E31E24] to-[#C71A20] rounded-full flex items-center justify-center text-white font-bold">
                        {{ substr(($depense->utilisateur?->name ?? 'Utilisateur supprimé'), 0, 1) }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">{{ ($depense->utilisateur?->name ?? 'Utilisateur supprimé') }}</p>
                        <p class="text-sm text-gray-600">{{ ($depense->utilisateur?->role ?? 'N/A') }}</p>
                    </div>
                </div>
            </div>

            <!-- Dates -->
            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <x-icon name="calendar-days" class="text-[#D7232A] mr-3" />
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
