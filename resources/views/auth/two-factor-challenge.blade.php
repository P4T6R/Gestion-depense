<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de sécurité - ARTS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center px-6">
    <main class="w-full max-w-md rounded-2xl bg-white p-8 shadow-lg">
        <h1 class="text-2xl font-bold">Vérification de sécurité</h1>
        <p class="mt-2 text-gray-600">Saisissez le code à 6 chiffres de votre application ou un code de secours.</p>
        <form method="POST" action="{{ route('two-factor.challenge.verify') }}" class="mt-6 space-y-5">
            @csrf
            <input name="code" required autofocus autocomplete="one-time-code" maxlength="32" class="w-full rounded-xl border-gray-300 px-4 py-3 font-mono tracking-widest" placeholder="123456">
            @error('code')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            <button class="w-full rounded-xl bg-[#E31E24] px-5 py-3 font-bold text-white">Vérifier</button>
        </form>
    </main>
</body>
</html>
