<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - ARTS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center px-6">
    <main class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">
        <h1 class="text-2xl font-bold text-gray-900">Mot de passe oublié</h1>
        <p class="mt-2 text-gray-600">Saisissez votre adresse professionnelle. Le lien reçu sera temporaire et utilisable une seule fois.</p>

        @if (session('status'))
            <div class="mt-6 rounded-lg bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700">Email professionnel</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                    class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-[#E31E24] focus:ring-[#E31E24]">
                @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button class="w-full rounded-xl bg-[#E31E24] px-5 py-3 font-bold text-white hover:bg-[#C71A20]">Envoyer le lien sécurisé</button>
        </form>
        <a href="{{ route('login') }}" class="mt-6 block text-center text-sm font-semibold text-[#E31E24]">Retour à la connexion</a>
    </main>
</body>
</html>
