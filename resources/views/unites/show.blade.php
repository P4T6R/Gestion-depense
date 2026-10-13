@extends('layouts.admin')

@section('title', 'Détails Unité - StratAction Consulting')
@section('header', 'Détails de l\'Unité')

@section('content')
<div class="space-y-6">
    <nav class="flex text-sm text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-[#E31E24]">Dashboard</a>
        <span class="mx-2">/</span>
        <a href="{{ route('unites.index') }}" class="hover:text-[#E31E24]">Unités</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium">{{ $unite->nom }}</span>
    </nav>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl">{{ $unite->nom }}</h1>
            <p class="text-gray-600 mt-2">Symbole: <span class="font-mono font-bold text-lg">{{ $unite->symbole }}</span></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('unites.edit', $unite->id) }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                <x-icon name="square-pen" class="" />
                <span>Modifier</span>
            </a>
            <form action="{{ route('unites.destroy', $unite->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">
                    <x-icon name="trash-2" class="" />
                    <span>Supprimer</span>
                </button>
            </form>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Informations</h2>
                <div class="space-y-4">
                    <div>
                        <p class="text-gray-600 text-sm">Nom</p>
                        <p class="text-gray-900 font-semibold text-lg">{{ $unite->nom }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Symbole</p>
                        <p class="text-gray-900 font-semibold font-mono text-lg">{{ $unite->symbole }}</p>
                    </div>
                    @if($unite->description)
                    <div>
                        <p class="text-gray-600 text-sm">Description</p>
                        <p class="text-gray-900 mt-2">{{ $unite->description }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Statut</h2>
                @if($unite->is_active)
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold">Active</span>
                    </div>
                @else
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-semibold">Inactive</span>
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-[#E7E5E1] bg-white p-5 sm:p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">
                    <x-icon name="receipt" class="text-[#D7232A] mr-2" />Dépenses
                </h2>
                <p class="text-3xl font-bold text-[#E31E24]">{{ $unite->depenses_count ?? 0 }}</p>
                <p class="text-gray-600 text-sm mt-2">dépenses associées</p>
            </div>
        </div>
    </div>
</div>
@endsection
