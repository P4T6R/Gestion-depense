@extends('layouts.admin')

@section('title', 'Fournisseurs')
@section('header', 'Registre fournisseurs')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-[#232323]">Fournisseurs</h1>
            <p class="mt-1 text-sm text-gray-600">Un même fournisseur est reconnu malgré les accents, espaces et différences de casse.</p>
        </div>
        <a href="{{ route('depenses.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-[#E7E5E1] bg-white px-4 text-sm font-semibold text-[#232323] hover:bg-[#F7F6F3]"><x-icon name="chevron-left" class="size-4" /> Dépenses</a>
    </div>

    @if(session('success'))
        <p role="status" class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</p>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><p class="font-semibold">Corrigez le formulaire :</p><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <section class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-6" aria-label="Ajouter un fournisseur">
        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold"><x-icon name="plus" class="size-5 text-[#D7232A]" />Ajouter un fournisseur</h2>
        <form action="{{ route('fournisseurs.store') }}" method="POST" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @csrf
            <div><label for="nouveau-nom" class="mb-1 block text-sm font-medium">Nom *</label><input id="nouveau-nom" name="nom" value="{{ old('nom') }}" required maxlength="255" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 focus:border-[#D7232A] focus:ring-[#D7232A]"></div>
            <div><label for="nouveau-telephone" class="mb-1 block text-sm font-medium">Téléphone</label><input id="nouveau-telephone" name="telephone" value="{{ old('telephone') }}" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 focus:border-[#D7232A] focus:ring-[#D7232A]"></div>
            <div><label for="nouveau-email" class="mb-1 block text-sm font-medium">E-mail</label><input id="nouveau-email" name="email" type="email" value="{{ old('email') }}" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 focus:border-[#D7232A] focus:ring-[#D7232A]"></div>
            <div><label for="nouveau-adresse" class="mb-1 block text-sm font-medium">Adresse</label><input id="nouveau-adresse" name="adresse" value="{{ old('adresse') }}" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 focus:border-[#D7232A] focus:ring-[#D7232A]"></div>
            <input type="hidden" name="is_active" value="1">
            <div class="md:col-span-2 xl:col-span-4"><button class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-[#D7232A] px-5 text-sm font-semibold text-white hover:bg-[#B91C23]"><x-icon name="save" class="size-4" /> Enregistrer</button></div>
        </form>
    </section>

    <section class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-6" aria-label="Liste des fournisseurs">
        <form method="GET" action="{{ route('fournisseurs.index') }}" class="mb-5 flex flex-wrap gap-2">
            <label for="recherche-fournisseur" class="sr-only">Rechercher un fournisseur</label>
            <input id="recherche-fournisseur" name="q" value="{{ $search }}" placeholder="Rechercher par nom" class="min-h-11 flex-1 rounded-xl border border-gray-200 px-4 focus:border-[#D7232A] focus:ring-[#D7232A]">
            <button class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-[#E7E5E1] px-4 text-sm font-semibold hover:bg-[#F7F6F3]"><x-icon name="search" class="size-4" /> Rechercher</button>
        </form>
        <div class="space-y-3">
            @forelse($fournisseurs as $fournisseur)
                <details class="group rounded-xl border border-[#E7E5E1] p-4 open:bg-[#FCFBF9]">
                    <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3">
                        <span class="flex items-center gap-3"><x-icon name="truck" class="size-5 text-[#D7232A]" /><span class="font-semibold">{{ $fournisseur->nom }}</span><span class="rounded-full px-2 py-0.5 text-xs {{ $fournisseur->is_active ? 'bg-green-50 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $fournisseur->is_active ? 'Actif' : 'Inactif' }}</span></span>
                        <span class="flex items-center gap-3 text-sm text-gray-600">{{ $fournisseur->depenses_count }} facture(s)<x-icon name="chevron-down" class="size-4 transition-transform group-open:rotate-180" /></span>
                    </summary>
                    <form action="{{ route('fournisseurs.update', $fournisseur) }}" method="POST" class="mt-5 grid gap-4 border-t border-[#E7E5E1] pt-5 md:grid-cols-2 xl:grid-cols-4">
                        @csrf @method('PUT')
                        <div><label for="nom-{{ $fournisseur->id }}" class="mb-1 block text-sm font-medium">Nom *</label><input id="nom-{{ $fournisseur->id }}" name="nom" value="{{ $fournisseur->nom }}" required class="w-full rounded-xl border border-gray-200 px-3 py-2.5"></div>
                        <div><label for="telephone-{{ $fournisseur->id }}" class="mb-1 block text-sm font-medium">Téléphone</label><input id="telephone-{{ $fournisseur->id }}" name="telephone" value="{{ $fournisseur->telephone }}" class="w-full rounded-xl border border-gray-200 px-3 py-2.5"></div>
                        <div><label for="email-{{ $fournisseur->id }}" class="mb-1 block text-sm font-medium">E-mail</label><input id="email-{{ $fournisseur->id }}" name="email" type="email" value="{{ $fournisseur->email }}" class="w-full rounded-xl border border-gray-200 px-3 py-2.5"></div>
                        <div><label for="adresse-{{ $fournisseur->id }}" class="mb-1 block text-sm font-medium">Adresse</label><input id="adresse-{{ $fournisseur->id }}" name="adresse" value="{{ $fournisseur->adresse }}" class="w-full rounded-xl border border-gray-200 px-3 py-2.5"></div>
                        <div class="flex items-center gap-3 md:col-span-2 xl:col-span-4"><label for="actif-{{ $fournisseur->id }}" class="text-sm font-medium">État</label><select id="actif-{{ $fournisseur->id }}" name="is_active" class="rounded-xl border border-gray-200 px-3 py-2.5"><option value="1" @selected($fournisseur->is_active)>Actif</option><option value="0" @selected(! $fournisseur->is_active)>Inactif</option></select><button class="min-h-11 rounded-xl bg-[#D7232A] px-5 text-sm font-semibold text-white hover:bg-[#B91C23]">Enregistrer</button></div>
                    </form>
                </details>
            @empty
                <p class="py-8 text-center text-sm text-gray-600">Aucun fournisseur trouvé.</p>
            @endforelse
        </div>
        <div class="mt-5">{{ $fournisseurs->links() }}</div>
    </section>
</div>
@endsection
