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
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><p class="font-semibold">Veuillez corriger les informations :</p><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @if($rapprochements->isNotEmpty())
        <section role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950" aria-label="Factures à vérifier">
            <p class="flex items-center gap-2 font-semibold"><x-icon name="triangle-alert" class="size-5" /> Factures à vérifier</p>
            <p class="mt-1">Même fournisseur, montant et date de facture. Vérifiez avant de prendre une décision.</p>
            <ul class="mt-2 flex flex-wrap gap-2">@foreach($rapprochements as $proche)<li><a class="font-semibold underline underline-offset-2" href="{{ route('depenses.show', $proche) }}">Facture #{{ $proche->id }}{{ $proche->numero_facture ? ' — '.$proche->numero_facture : '' }}</a></li>@endforeach</ul>
        </section>
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

            @can('approve', $depense)
            <form action="{{ route('depenses.approve', $depense->id) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-[#D7232A] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#B91C23]">
                    <x-icon name="check" class="" />
                    <span>Approuver</span>
                </button>
            </form>
            @endcan
            @if(auth()->user()->role === 'directeur' && auth()->user()->can('reject', $depense))
            <button type="button" @click="directorRejectOpen = !directorRejectOpen" :aria-expanded="directorRejectOpen" aria-controls="director-reject-form" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">
                <x-icon name="x" class="size-4" />
                <span>Rejeter</span>
            </button>
            @endif
        </div>
    </div>

    @if(auth()->user()->role === 'directeur' && auth()->user()->can('reject', $depense))
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
                @elseif($depense->statut_validation === 'en_attente' && $depense->circuit_validation_directe)
                    <p class="rounded-xl bg-[#F7F6F3] p-4 text-sm text-gray-700">Facture créée par un directeur : la comptable prend directement la décision. Aucun paiement n'est possible avant sa validation.</p>
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
                    <p class="mt-3 text-sm text-gray-600">Corrigez la facture et enregistrez-la pour la soumettre de nouveau {{ $depense->circuit_validation_directe ? 'à la comptable' : 'au directeur' }}.</p>
                    @endcan
                </div>
                @endif
            </div>

            @if(auth()->user()->role === 'comptable')
                @can('validate', $depense)
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

    @if($depense->statut_validation === 'validee')
        <section class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-8" aria-labelledby="paiements-title">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 id="paiements-title" class="flex items-center gap-2 text-xl font-bold text-gray-900"><x-icon name="banknote" class="size-5 text-[#D7232A]" /> Paiements de la facture</h2>
                    <p class="mt-1 text-sm text-gray-600">Seuls les règlements enregistrés et non annulés figurent dans le montant payé.</p>
                </div>
                <span class="rounded-full bg-[#F8EEEE] px-3 py-1 text-xs font-semibold text-[#C31F28]">{{ (float) $resteAPayer <= 0 && ! $historiqueNonRapproche ? 'Payée' : ((float) $montantPaye > 0 ? 'Partiellement payée' : 'À payer') }}</span>
            </div>
            <dl class="mt-5 grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-[#FCFBF9] p-4"><dt class="text-sm text-gray-600">Montant validé</dt><dd class="mt-1 text-xl font-semibold">{{ number_format((float) $depense->prix_total, 2, ',', ' ') }} F</dd></div>
                <div class="rounded-xl bg-[#FCFBF9] p-4"><dt class="text-sm text-gray-600">Réellement payé</dt><dd class="mt-1 text-xl font-semibold">{{ number_format((float) $montantPaye, 2, ',', ' ') }} F</dd></div>
                <div class="rounded-xl bg-[#FCFBF9] p-4"><dt class="text-sm text-gray-600">Reste à payer</dt><dd class="mt-1 text-xl font-semibold">{{ number_format((float) $resteAPayer, 2, ',', ' ') }} F</dd></div>
            </dl>
            @if($historiqueNonRapproche)
                <p role="status" class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">Cette facture était marquée payée avant la création du registre. Son règlement n’entre pas encore dans les montants réellement payés. La comptable doit saisir sa date et sa référence pour le rapprocher.</p>
            @endif

            @can('recordPayment', $depense)
                @if((float) $resteAPayer > 0)
                    <form action="{{ route('depenses.paiements.store', $depense) }}" method="POST" class="mt-6 rounded-xl border border-[#E7E5E1] p-4 sm:p-5">
                        @csrf
                        <h3 class="mb-4 font-semibold text-gray-900">{{ $historiqueNonRapproche ? 'Rapprocher le paiement historique' : 'Enregistrer un paiement' }}</h3>
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <div><label for="paiement-montant" class="mb-1 block text-sm font-medium text-gray-700">Montant (F) *</label><input id="paiement-montant" name="montant" type="number" step="0.01" min="0.01" max="{{ $resteAPayer }}" value="{{ old('montant', $resteAPayer) }}" required class="w-full rounded-xl border border-gray-200 px-3 py-2.5"></div>
                            <div><label for="paiement-date" class="mb-1 block text-sm font-medium text-gray-700">Date du paiement *</label><input id="paiement-date" name="date_paiement" type="date" max="{{ now()->toDateString() }}" value="{{ old('date_paiement', now()->toDateString()) }}" required class="w-full rounded-xl border border-gray-200 px-3 py-2.5"></div>
                            <div><label for="paiement-mode" class="mb-1 block text-sm font-medium text-gray-700">Mode *</label><select id="paiement-mode" name="mode" required class="w-full rounded-xl border border-gray-200 px-3 py-2.5"><option value="virement" @selected(old('mode') === 'virement')>Virement</option><option value="especes" @selected(old('mode') === 'especes')>Espèces</option><option value="cheque" @selected(old('mode') === 'cheque')>Chèque</option><option value="mobile_money" @selected(old('mode') === 'mobile_money')>Mobile money</option><option value="autre" @selected(old('mode') === 'autre')>Autre</option></select></div>
                            <div><label for="paiement-reference" class="mb-1 block text-sm font-medium text-gray-700">Référence ou reçu *</label><input id="paiement-reference" name="reference" value="{{ old('reference') }}" maxlength="255" required placeholder="N° de transaction ou de reçu" class="w-full rounded-xl border border-gray-200 px-3 py-2.5"></div>
                        </div>
                        @if($historiqueNonRapproche)
                            <label class="mt-4 flex items-start gap-2 text-sm text-gray-700"><input type="checkbox" name="reprise_historique" value="1" required @checked(old('reprise_historique')) class="mt-1 accent-[#D7232A]"><span>Je confirme que ce paiement historique couvre la totalité de la facture et que la date et la référence ont été vérifiées.</span></label>
                        @endif
                        <button class="mt-5 inline-flex min-h-11 items-center gap-2 rounded-xl bg-[#D7232A] px-5 text-sm font-semibold text-white hover:bg-[#B91C23]"><x-icon name="save" class="size-4" />Enregistrer le paiement</button>
                    </form>
                @endif
            @endcan

            <div class="mt-6">
                <h3 class="mb-3 font-semibold text-gray-900">Historique des règlements</h3>
                @forelse($depense->paiements->reverse() as $paiement)
                    <div class="mb-3 rounded-xl border border-[#E7E5E1] p-4 {{ $paiement->annule_le ? 'opacity-70' : '' }}">
                        <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="font-semibold">{{ number_format((float) $paiement->montant, 2, ',', ' ') }} F · {{ str_replace('_', ' ', ucfirst($paiement->mode)) }}</p><p class="mt-1 text-sm text-gray-600">{{ $paiement->date_paiement?->format('d/m/Y') }} · Réf. {{ $paiement->reference }} · {{ $paiement->enregistre_par_nom }}</p></div><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $paiement->annule_le ? 'bg-gray-100 text-gray-600' : 'bg-[#F8EEEE] text-[#C31F28]' }}">{{ $paiement->annule_le ? 'Annulé' : ($paiement->reprise_historique ? 'Reprise historique' : 'Enregistré') }}</span></div>
                        @if($paiement->annule_le)<p class="mt-2 text-sm text-gray-600">Annulé le {{ $paiement->annule_le->format('d/m/Y H:i') }} par {{ $paiement->annule_par_nom }} : {{ $paiement->motif_annulation }}</p>@endif
                        @if(! $paiement->annule_le)
                            @can('cancelPayment', $depense)
                                <details class="mt-3 border-t border-[#E7E5E1] pt-3 text-sm"><summary class="cursor-pointer font-medium text-[#C31F28]">Annuler ce paiement</summary><form action="{{ route('depenses.paiements.cancel', [$depense, $paiement]) }}" method="POST" class="mt-3 flex flex-wrap items-end gap-3">@csrf<div class="min-w-60 flex-1"><label for="annulation-{{ $paiement->id }}" class="mb-1 block font-medium">Motif d’annulation *</label><input id="annulation-{{ $paiement->id }}" name="motif_annulation" required minlength="10" maxlength="10000" class="w-full rounded-xl border border-gray-200 px-3 py-2.5"></div><button class="min-h-11 rounded-xl border border-red-200 px-4 font-semibold text-red-700 hover:bg-red-50">Confirmer l’annulation</button></form></details>
                            @endcan
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Aucun paiement enregistré pour cette facture.</p>
                @endforelse
            </div>
        </section>
    @endif

    <section class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-8" aria-labelledby="historique-title">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 id="historique-title" class="flex items-center gap-2 text-xl font-bold text-gray-900">
                    <x-icon name="clock-3" class="size-5 text-[#D7232A]" />Historique de la facture
                </h2>
                <p class="mt-1 text-sm text-gray-600">Chaque décision conserve la version de la facture examinée.</p>
            </div>
            <span class="rounded-full bg-[#F8EEEE] px-3 py-1 text-xs font-semibold text-[#C31F28]">Version {{ $depense->version_courante }}</span>
        </div>
        <ol class="space-y-3">
            @forelse($depense->historique->reverse() as $event)
                @php
                    $actionLabels = [
                        'etat_initial' => 'État initial connu',
                        'creation' => 'Création',
                        'modification' => 'Modification',
                        'resoumission' => 'Correction et nouvelle soumission',
                        'approbation' => 'Approbation du directeur',
                        'rejet' => 'Rejet',
                        'validation' => 'Validation comptable',
                        'paiement' => 'Paiement enregistré',
                        'reprise_paiement' => 'Paiement historique rapproché',
                        'annulation_paiement' => 'Paiement annulé',
                        'suppression' => 'Suppression',
                    ];
                    $snapshot = $event->instantane ?? [];
                @endphp
                <li class="rounded-xl border border-[#E7E5E1] bg-[#FCFBFA] p-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $actionLabels[$event->action] ?? $event->action }} · v{{ $event->version }}</p>
                            <p class="mt-1 text-sm text-gray-600">{{ $event->acteur_nom ?? 'État antérieur au suivi' }}@if($event->acteur_role) · {{ str_replace('_', ' ', $event->acteur_role) }}@endif</p>
                        </div>
                        <time datetime="{{ $event->created_at?->toIso8601String() }}" class="text-xs text-gray-500">{{ $event->created_at?->format('d/m/Y H:i:s') }}</time>
                    </div>
                    @if($event->motif)
                        <p class="mt-3 whitespace-pre-line rounded-lg border border-red-100 bg-red-50 p-3 text-sm text-red-800"><strong>Motif :</strong> {{ $event->motif }}</p>
                    @endif
                    <details class="mt-3 border-t border-[#E7E5E1] pt-3 text-sm">
                        <summary class="cursor-pointer font-semibold text-[#C31F28]">Voir la version examinée</summary>
                        <div class="mt-3 grid gap-3 text-gray-700 sm:grid-cols-2">
                            <p><span class="text-gray-500">N° facture :</span> {{ $snapshot['numero_facture'] ?? 'Non renseigné' }}</p>
                            <p><span class="text-gray-500">Fournisseur :</span> {{ $snapshot['fournisseur'] ?? 'Non renseigné' }}</p>
                            <p><span class="text-gray-500">Montant :</span> {{ number_format((float) ($snapshot['prix_total'] ?? 0), 2, ',', ' ') }} F</p>
                            <p><span class="text-gray-500">Statut :</span> {{ str_replace('_', ' ', $event->statut_apres) }}</p>
                        </div>
                        @if(!empty($snapshot['lignes']))
                            <ul class="mt-3 space-y-1 border-t border-[#E7E5E1] pt-3">
                                @foreach($snapshot['lignes'] as $line)
                                    <li>{{ $line['designation'] ?? 'Article' }} · {{ $line['quantite'] ?? 0 }} × {{ number_format((float) ($line['prix_unitaire'] ?? 0), 2, ',', ' ') }} F</li>
                                @endforeach
                            </ul>
                        @elseif(!empty($snapshot['nom_produit']))
                            <p class="mt-3">{{ $snapshot['nom_produit'] }} · {{ $snapshot['quantite'] ?? 0 }} × {{ number_format((float) ($snapshot['prix_unitaire'] ?? 0), 2, ',', ' ') }} F</p>
                        @endif
                        @if(!empty($snapshot['justificatifs']))
                            <p class="mt-3 border-t border-[#E7E5E1] pt-3 text-gray-500">Justificatifs : {{ collect($snapshot['justificatifs'])->pluck('nom_fichier')->join(', ') }}</p>
                        @endif
                    </details>
                </li>
            @empty
                <li class="rounded-xl border border-dashed border-[#E7E5E1] p-4 text-sm text-gray-600">Aucun événement enregistré depuis l’activation de l’historique. Les décisions antérieures ne peuvent pas être reconstituées.</li>
            @endforelse
        </ol>
    </section>
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
