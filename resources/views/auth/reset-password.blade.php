<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe - ARTS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center px-6">
    <main class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">
        <h1 class="text-2xl font-bold text-gray-900">Définir un nouveau mot de passe</h1>
        <p class="mt-2 text-sm text-gray-600">Minimum 12 caractères avec majuscule, minuscule, chiffre et symbole.</p>
        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email" class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3">
                @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700">Nouveau mot de passe</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3">
                @error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700">Confirmation</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3">
            </div>
            <button class="w-full rounded-xl bg-[#E31E24] px-5 py-3 font-bold text-white hover:bg-[#C71A20]">Enregistrer le mot de passe</button>
        </form>
    </main>
</body>
</html>
