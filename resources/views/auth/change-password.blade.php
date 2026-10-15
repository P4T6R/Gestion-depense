<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sécuriser votre compte - ARTS Construction & Services</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#F7F6F3] text-[#232323] antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6">
        <div class="w-full max-w-lg">
            <div class="mb-6 text-center">
                <img src="/images/arts-logo.png" class="mx-auto h-20 w-auto" alt="ARTS Construction & Services">
            </div>

            <section class="overflow-hidden rounded-2xl border border-[#E7E5E1] bg-white shadow-xl shadow-black/5" aria-labelledby="password-title">
                <div class="border-b border-[#E7E5E1] bg-[#FCFBF9] px-6 py-6 sm:px-8">
                    <div class="mb-4 flex size-12 items-center justify-center rounded-full bg-[#F8EEEE] text-[#C31F28]">
                        <x-icon name="shield-half" class="size-6" />
                    </div>
                    <h1 id="password-title" class="text-2xl font-bold tracking-tight">Créez votre mot de passe personnel</h1>
                    <p class="mt-2 text-sm leading-6 text-[#666]">
                        Pour protéger votre compte, le mot de passe transmis par l’administrateur doit être remplacé avant d’accéder à l’application.
                    </p>
                    <p class="mt-3 truncate text-sm font-medium text-[#333]">{{ auth()->user()->email }}</p>
                </div>

                <form action="{{ route('password.change.required.update') }}" method="POST" class="space-y-5 px-6 py-7 sm:px-8">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="mb-2 block text-sm font-semibold text-[#333]">Mot de passe temporaire</label>
                        <input
                            id="current_password"
                            name="current_password"
                            type="password"
                            autocomplete="current-password"
                            required
                            autofocus
                            class="w-full rounded-xl border px-4 py-3.5 outline-hidden transition focus:border-[#D7232A] focus:ring-2 focus:ring-[#D7232A]/20 {{ $errors->has('current_password') ? 'border-red-500' : 'border-[#D6D3CE]' }}"
                        >
                        @error('current_password')<p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-semibold text-[#333]">Nouveau mot de passe</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="w-full rounded-xl border px-4 py-3.5 outline-hidden transition focus:border-[#D7232A] focus:ring-2 focus:ring-[#D7232A]/20 {{ $errors->has('password') ? 'border-red-500' : 'border-[#D6D3CE]' }}"
                        >
                        @error('password')<p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
                        <p class="mt-2 text-xs leading-5 text-[#777]">Au moins 12 caractères, avec une majuscule, une minuscule, un chiffre et un symbole.</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-[#333]">Confirmer le nouveau mot de passe</label>
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="w-full rounded-xl border border-[#D6D3CE] px-4 py-3.5 outline-hidden transition focus:border-[#D7232A] focus:ring-2 focus:ring-[#D7232A]/20"
                        >
                    </div>

                    <button type="submit" class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#D7232A] px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-[#BD1F25] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#D7232A]">
                        <x-icon name="lock-keyhole" class="size-5" />
                        Enregistrer et continuer
                    </button>
                </form>
            </section>

            <form action="{{ route('logout') }}" method="POST" class="mt-5 text-center">
                @csrf
                <button type="submit" class="text-sm font-medium text-[#666] underline-offset-4 hover:text-[#C31F28] hover:underline">Se déconnecter</button>
            </form>
        </div>
    </main>
</body>
</html>
