<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - ARTS Constructions & Services</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-white overflow-hidden">
    
    <div class="flex min-h-screen">
        <!-- Panneau gauche - Rouge ARTS avec info -->
        <div class="hidden lg:flex lg:w-1/2 bg-linear-to-br from-[#E31E24] to-[#C71A20] relative overflow-hidden">
            <!-- Motif de fond décoratif -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full translate-x-1/2 translate-y-1/2"></div>
            </div>
            
            <div class="relative z-10 flex flex-col justify-center px-16 text-white">
                <!-- Logo ARTS -->
                <div class="mb-8 flex justify-center">
                    <div class="bg-white rounded-2xl px-5 py-4 shadow-2xl flex items-center justify-center">
                        <img src="/images/arts-logo.png" class="h-32 w-auto" alt="ARTS" onerror="this.outerHTML='<div class=\'text-4xl font-black text-[#E31E24]\'>ARTS</div>'">
                    </div>
                </div>

                <!-- Titre principal -->
                <h1 class="text-4xl font-extrabold leading-tight mb-5">
                    Gérez vos<br>
                    chantiers avec<br>
                    précision.
                </h1>

                <!-- Description -->
                <p class="text-xl text-white/90 mb-16 max-w-lg leading-relaxed">
                    La plateforme professionnelle de suivi des dépenses pour les chantiers BTP en Afrique de l'Ouest.
                </p>

                <!-- Statistiques -->
                <div class="grid grid-cols-2 gap-6 max-w-lg">
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                        <p class="text-4xl font-bold mb-2">35M+</p>
                        <p class="text-white/80 text-sm font-medium">Budgets gérés</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                        <p class="text-4xl font-bold mb-2">100%</p>
                        <p class="text-white/80 text-sm font-medium">Transparence</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panneau droit - Formulaire -->
        <div class="w-full lg:w-1/2 flex items-center justify-center px-8 lg:px-16 bg-gray-50">
            <div class="w-full max-w-md">
                <!-- En-tête -->
                <div class="mb-10">
                    <h2 class="text-4xl font-bold text-gray-900 mb-3">Bon retour !</h2>
                    <p class="text-gray-600 text-lg">Connectez-vous à votre espace de gestion.</p>
                </div>

                <!-- Formulaire -->
                <form action="{{ route('login') }}" method="POST" class="space-y-6">
                    @csrf
                    
                    @if ($errors->any())
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                            <div class="flex items-center">
                                <x-icon name="circle-alert" class="text-red-500 mr-3" />
                                <p class="text-sm text-red-700 font-medium">
                                    {{ $errors->first() }}
                                </p>
                            </div>
                        </div>
                    @endif

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email professionnel</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required 
                            class="w-full px-4 py-4 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#E31E24] focus:border-transparent transition-all outline-hidden text-gray-900 placeholder-gray-400"
                            placeholder="votre@email.com">
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Mot de passe</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required 
                                class="w-full px-4 py-4 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#E31E24] focus:border-transparent transition-all outline-hidden text-gray-900"
                                placeholder="••••••••">
                            <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <x-icon name="eye" id="toggleEye" /><x-icon name="eye-off" id="toggleEyeOff" class="hidden" />
                            </button>
                        </div>
                    </div>

                    <!-- Remember & Forgot -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer group">
                            <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-[#E31E24] focus:ring-[#E31E24] focus:ring-offset-0">
                            <span class="ml-2 text-sm text-gray-700 group-hover:text-gray-900">Se souvenir de moi</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="text-sm font-semibold text-[#E31E24] hover:text-[#C71A20] transition-colors">
                            Mot de passe oublié ?
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                        class="w-full bg-linear-to-r from-[#E31E24] to-[#C71A20] text-white font-bold py-4 px-6 rounded-xl shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all duration-200 active:scale-95">
                        Se connecter
                    </button>
                </form>

                <!-- Support -->
                <div class="mt-10 pt-8 border-t border-gray-200 text-center">
                    <p class="text-sm text-gray-600">
                        Besoin d'aide ? Contactez l'administrateur
                        <a href="mailto:support@arts.bf" class="block mt-1 font-semibold text-[#E31E24] hover:underline">
                            support@arts.bf
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleEye = document.getElementById('toggleEye');
            const toggleEyeOff = document.getElementById('toggleEyeOff');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleEye.classList.add('hidden');
                toggleEyeOff.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                toggleEyeOff.classList.add('hidden');
                toggleEye.classList.remove('hidden');
            }
        }
    </script>

</body>
</html>
