@extends('layouts.admin')

@section('title', 'Double authentification')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Double authentification</h1>
        <p class="mt-2 text-gray-600">Protégez votre compte avec une application TOTP telle que Google Authenticator, Microsoft Authenticator ou 1Password.</p>
    </div>

    @if (session('success'))<div class="rounded-lg bg-green-50 p-4 text-green-800">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="rounded-lg bg-red-50 p-4 text-red-700">{{ $errors->first() }}</div>@endif

    @if ($recoveryCodes)
        <section class="rounded-xl border-2 border-amber-300 bg-amber-50 p-6">
            <h2 class="font-bold text-amber-900">Codes de secours — affichés une seule fois</h2>
            <p class="mt-1 text-sm text-amber-800">Conservez-les hors ligne. Chaque code ne fonctionne qu’une fois.</p>
            <div class="mt-4 grid grid-cols-2 gap-2 font-mono text-sm">@foreach ($recoveryCodes as $code)<span>{{ $code }}</span>@endforeach</div>
        </section>
    @endif

    @if ($user->two_factor_confirmed_at)
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <p class="font-semibold text-green-700">Double authentification active depuis le {{ $user->two_factor_confirmed_at->format('d/m/Y H:i') }}.</p>
            <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-5 space-y-4">
                @csrf @method('DELETE')
                <input type="password" name="current_password" required autocomplete="current-password" placeholder="Mot de passe actuel" class="w-full rounded-lg border-gray-300">
                <button class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-white">Désactiver</button>
            </form>
        </section>
    @elseif ($user->two_factor_secret)
        <section class="rounded-xl bg-white p-6 shadow-sm space-y-4">
            <h2 class="font-bold">1. Ajouter le compte à l’application</h2>
            <p class="text-sm text-gray-600">Saisissez manuellement ce secret :</p>
            <code class="block break-all rounded-lg bg-gray-100 p-4 font-mono text-sm">{{ $user->two_factor_secret }}</code>
            <details class="text-xs text-gray-500"><summary>URI technique</summary><code class="mt-2 block break-all">{{ $provisioningUri }}</code></details>
            <h2 class="font-bold">2. Confirmer le code à 6 chiffres</h2>
            <form method="POST" action="{{ route('two-factor.confirm') }}" class="flex gap-3">
                @csrf
                <input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code" class="rounded-lg border-gray-300 font-mono tracking-widest" placeholder="123456">
                <button class="rounded-lg bg-[#E31E24] px-4 py-2 font-semibold text-white">Confirmer</button>
            </form>
        </section>
    @else
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('two-factor.enable') }}" class="space-y-4">
                @csrf
                <label for="current_password" class="block font-semibold">Confirmez votre mot de passe</label>
                <input id="current_password" type="password" name="current_password" required autocomplete="current-password" class="w-full rounded-lg border-gray-300">
                <button class="rounded-lg bg-[#E31E24] px-4 py-2 font-semibold text-white">Commencer l’activation</button>
            </form>
        </section>
    @endif
</div>
@endsection
