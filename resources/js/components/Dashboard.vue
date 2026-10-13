<script setup>
import { ref, onMounted } from 'vue';

const scrollY = ref(0);
const currentSlide = ref(0);
const isMenuOpen = ref(false);

const slides = [
  {
    title: "Management Stratégique",
    subtitle: "Etudes et Conseils",
    description: "Optimisez vos performances avec notre expertise en management stratégique",
    bgGradient: "from-gray-900 via-blue-900 to-gray-900",
    image: "/images/1.jpg"
  },
  {
    title: "Accompagnement Opérationnel",
    subtitle: "Solutions sur Mesure",
    description: "Transformez vos défis en opportunités avec un accompagnement personnalisé",
    bgGradient: "from-blue-600 via-red-600 to-blue-700",
    image: "/images/2.jpg"
  },
  {
    title: "Formation & Développement",
    subtitle: "Renforcement des Compétences",
    description: "Développez le potentiel de vos équipes avec des formations adaptées",
    bgGradient: "from-blue-900 via-indigo-900 to-gray-900",
    image: "/images/3.jpg",
  }
];

const nextSlide = () => {
  currentSlide.value = (currentSlide.value + 1) % slides.length;
};

const prevSlide = () => {
  currentSlide.value = (currentSlide.value - 1 + slides.length) % slides.length;
};

const goToSlide = (index) => {
  currentSlide.value = index;
};

onMounted(() => {
  // Auto-change slides
  setInterval(() => {
    currentSlide.value = (currentSlide.value + 1) % slides.length;
  }, 5000);

  // Scroll observer
  window.addEventListener('scroll', () => {
    scrollY.value = window.scrollY;
  });

  // Intersection observer for animations
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
      }
    });
  }, { threshold: 0.3 });

  document.querySelectorAll('.reveal-section, .fade-item, .valeur-card, .observe')
    .forEach(el => observer.observe(el));
});
</script>

<template>
  <div class="min-h-screen bg-black overflow-hidden">
    <!-- TOP BAR -->
    <div class="w-full bg-[#201d51] text-white text-xs sm:text-sm py-2">
      <div class="max-w-7xl mx-auto px-4">
        <!-- Desktop View -->
        <div class="hidden lg:flex justify-between items-center">
          <div class="flex items-center space-x-4 xl:space-x-6">
            <span class="flex items-center space-x-1">
              <svg class="w-3 h-3 xl:w-4 xl:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
              <span class="text-xs xl:text-sm">stratactionconsulting@gmail.com</span>
            </span>
            <span class="flex items-center space-x-1">
              <svg class="w-3 h-3 xl:w-4 xl:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              </svg>
              <span class="text-xs xl:text-sm">Somdgande</span>
            </span>
          </div>

          <div class="flex items-center space-x-3 xl:space-x-5">
            <span class="text-yellow-300 font-semibold text-xs xl:text-sm">Vérification certificat</span>
            <span class="flex items-center space-x-2">
              <span class="text-xs xl:text-sm">Suivez-nous sur</span>
              <i class="fa-brands fa-facebook text-white text-base xl:text-lg"></i>
              <i class="fa-brands fa-linkedin text-white text-base xl:text-lg"></i>
            </span>
          </div>
        </div>

        <!-- Mobile/Tablet View -->
        <div class="lg:hidden flex justify-center items-center space-x-4">
          <span class="flex items-center space-x-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <span class="text-xs">stratactionconsulting@gmail.com</span>
          </span>
        </div>
      </div>
    </div>

    <!-- MAIN NAV -->
    <nav class="bg-white shadow-xs py-3 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between">
          <!-- Logo -->
          <div class="flex items-center space-x-3">
            <img src="/images/er.png" class="h-12 sm:h-16 lg:h-20" alt="Karray Group">
          </div>

          <!-- Desktop Menu -->
          <ul class="hidden xl:flex items-center space-x-4 font-semibold text-[#201d51]">
            <li><a href="/" class="relative pb-1 hover:text-blue-600 transition">Accueil</a></li>
            <li><a href="/domaines" class="relative pb-1 hover:text-blue-600 transition">Domaines</a></li>
            <li><a href="/formations" class="relative pb-1 hover:text-blue-600 transition">Formations</a></li>
            <li><a href="/actualites" class="relative pb-1 hover:text-blue-600 transition">Actualité</a></li>
            <li><a href="/contact" class="relative pb-1 hover:text-blue-600 transition">Contact</a></li>
          </ul>

          <!-- Auth Buttons & Hamburger -->
          <div class="flex items-center space-x-3 sm:space-x-4">
            <div class="hidden lg:flex items-center space-x-2 bg-gray-100 px-3 py-2 rounded-full">
              <a href="/login" class="text-blue-600 hover:text-blue-500 transition font-medium text-sm">
                Connexion
              </a>
              <a href="/register" class="px-4 py-2 rounded-full font-semibold text-white bg-linear-to-r from-blue-500 to-blue-600 shadow-md text-sm">
                S'inscrire
              </a>
            </div>

            <!-- Hamburger Button -->
            <button 
              @click="isMenuOpen = !isMenuOpen"
              class="xl:hidden p-2 text-[#201d51] hover:bg-gray-100 rounded-lg transition"
            >
              <svg v-if="!isMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
              </svg>
              <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Mobile Menu -->
        <transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 -translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-2"
        >
          <div v-if="isMenuOpen" class="xl:hidden mt-4 pb-4 border-t border-gray-200">
            <ul class="flex flex-col space-y-3 pt-4">
              <li><a href="/" class="block py-2 px-4 text-[#201d51] hover:text-blue-600 rounded-lg">Accueil</a></li>
              <li><a href="/domaines" class="block py-2 px-4 text-[#201d51] hover:text-blue-600 rounded-lg">Domaines</a></li>
              <li><a href="/formations" class="block py-2 px-4 text-[#201d51] hover:text-blue-600 rounded-lg">Formations</a></li>
              <li><a href="/contact" class="block py-2 px-4 text-[#201d51] hover:text-blue-600 rounded-lg">Contact</a></li>
            </ul>
          </div>
        </transition>
      </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative min-h-screen flex items-center justify-center overflow-hidden pt-20">
      <!-- Background carousel -->
      <transition name="zoom-fade" mode="out-in">
        <div 
          :key="currentSlide"
          class="absolute inset-0 bg-cover bg-center"
          :style="{
            backgroundColor: '#000',
            backgroundImage: `linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)), url(${slides[currentSlide].image})`
          }"
        >
          <div class="absolute inset-0 opacity-20">
            <div class="absolute top-0 left-0 w-96 h-96 bg-blue-500 rounded-full filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-blue-500 rounded-full filter blur-3xl animate-pulse" style="animation-delay: 1s"></div>
          </div>
        </div>
      </transition>

      <!-- Navigation Arrows -->
      <button 
        @click="prevSlide"
        class="absolute left-8 z-20 bg-white/10 backdrop-blur-md p-4 rounded-full hover:bg-white/20 transition"
      >
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>

      <button 
        @click="nextSlide"
        class="absolute right-8 z-20 bg-white/10 backdrop-blur-md p-4 rounded-full hover:bg-white/20 transition"
      >
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>

      <!-- Hero Content -->
      <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <transition name="fade" mode="out-in">
          <div :key="currentSlide" class="space-y-8">
            <div class="space-y-6">
              <h1 class="text-6xl md:text-7xl font-black text-white mb-6 leading-tight">
                {{ slides[currentSlide].title }}
              </h1>
              
              <p class="text-2xl md:text-3xl text-red-800 font-semibold mb-8">
                {{ slides[currentSlide].subtitle }}
              </p>

              <p class="text-xl md:text-2xl text-white/90 font-light max-w-3xl mx-auto">
                {{ slides[currentSlide].description }}
              </p>
            </div>

            <!-- Quote card -->
            <div class="max-w-4xl mx-auto bg-white/10 backdrop-blur-md p-8 rounded-2xl border border-white/20 shadow-2xl hover:scale-105 transition mt-12">
              <p class="text-xl md:text-2xl text-white leading-relaxed italic font-light px-8">
                La véritable performance est celle qui réussit à transformer les imperfections 
                et les défauts en une force pour atteindre le succès.
              </p>
              <p class="text-blue-400 mt-4 font-semibold text-lg">— Serge Zeller</p>
            </div>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row justify-center gap-6 pt-8">
              <a href="/register" class="group relative bg-linear-to-r from-blue-500 to-blue-600 text-white px-10 py-5 rounded-xl text-lg font-bold hover:shadow-2xl transition hover:scale-105 overflow-hidden inline-block">
                <span class="relative z-10 flex items-center justify-center gap-2">
                  Commencer Maintenant
                  <svg class="w-5 h-5 group-hover:translate-x-2 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                  </svg>
                </span>
              </a>
              
              <button class="relative border-2 border-blue-500 text-blue-500 px-10 py-5 rounded-xl text-lg font-bold hover:bg-blue-500 hover:text-white transition hover:scale-105 shadow-2xl">
                <span class="relative z-10">Découvrir Nos Services</span>
              </button>
            </div>
          </div>
        </transition>

        <!-- Slide indicators -->
        <div class="absolute bottom-100 left-1/2 transform -translate-x-1/2 flex gap-3 z-20">
          <button 
            v-for="(slide, index) in slides" 
            :key="index"
            @click="goToSlide(index)"
            class="transition-all duration-300"
            :class="currentSlide === index 
              ? 'w-12 h-3 bg-blue-500 rounded-full' 
              : 'w-3 h-3 bg-white/50 rounded-full hover:bg-white/80'"
          ></button>
        </div>

        <!-- Scroll indicator -->
        <div class="absolute bottom-1 left-1/2 transform -translate-x-1/2 animate-bounce">
          <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Director Message Section -->
    <div class="py-24 bg-linear-to-b from-gray-50 to-white relative overflow-hidden">
      <div class="flex justify-center mb-12">
        <div class="relative inline-block text-center">
          <h2 class="text-3xl font-bold text-gray-900">StratAction Consulting</h2>
          <svg class="absolute left-1/2 -bottom-4 w-20 -translate-x-1/2 text-green-500" 
              fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 50 10">
            <path d="M1 4 Q 9 0, 17 4 T 34 4 T 49 4" stroke-linecap="round"/>
          </svg>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
          <!-- Director info -->
          <div class="observe opacity-0 transform -translate-x-10 transition-all duration-1000">
            <div class="flex items-start space-x-6 mb-8">
              <div class="relative">
                <div class="absolute inset-0 -z-10 w-32 h-32 bg-blue-500/20 blur-2xl rounded-full animate-pulse"></div>
                <img 
                  src="/images/D.jpg" 
                  alt="Directeur"
                  class="w-32 h-32 object-cover rounded-2xl shadow-2xl hover:scale-105 transition"
                />
              </div>
              <div>
                <h3 class="text-3xl font-black text-gray-900 mb-2">Mot du Directeur</h3>
                <p class="text-blue-600 font-bold text-lg">Béliournibé Jean Thierry SOMDA</p>
                <p class="text-gray-600 text-sm">Expert en stratégie</p>
              </div>
            </div>
          </div>

          <!-- Message card -->
          <div class="observe opacity-0 transform translate-x-10 transition-all duration-1000">
            <div class="relative bg-white p-10 rounded-3xl shadow-2xl border-l-8 border-blue-500 hover:shadow-blue-500/20 transition">
              <p class="text-gray-700 leading-relaxed mb-6 text-lg">
                Dans un environnement turbulent, les organisations sont face à trois types de décisions :
              </p>
              
              <div class="space-y-5 mb-6">
                <div class="flex items-start group/item hover:translate-x-2 transition">
                  <div class="flex-shrink-0 w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-4">
                    <span class="text-green-500 font-bold">1</span>
                  </div>
                  <span class="text-gray-700 pt-2">Rester passive et subir le changement</span>
                </div>
                
                <div class="flex items-start group/item hover:translate-x-2 transition">
                  <div class="flex-shrink-0 w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-4">
                    <span class="text-green-500 font-bold">2</span>
                  </div>
                  <span class="text-gray-700 pt-2">Être réactive et attendre le changement</span>
                </div>
                
                <div class="flex items-start group/item hover:translate-x-2 transition">
                  <div class="flex-shrink-0 w-10 h-10 bg-linear-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center mr-4">
                    <span class="text-white font-bold">3</span>
                  </div>
                  <span class="text-gray-900 font-semibold pt-2">
                    Avoir une attitude <span class="text-green-600">prospective</span>
                  </span>
                </div>
              </div>
              
              <div class="bg-linear-to-r from-blue-50 to-transparent p-6 rounded-xl border-l-4 border-blue-500">
                <p class="text-gray-700 italic font-medium text-lg">
                  StratAction s'est donné pour mission d'accompagner vos structures.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="bg-[#201d51] text-white py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-4 gap-8">
          <div>
            <h3 class="font-bold mb-4">StratAction</h3>
            <p class="text-gray-400 text-sm">Management Stratégique, Etudes et Conseils</p>
          </div>

          <div>
            <h3 class="font-bold mb-4 text-blue-400">Services</h3>
            <ul class="space-y-2 text-gray-400 text-sm">
              <li>Management Stratégique</li>
              <li>Etudes & Conseil</li>
              <li>Accompagnement</li>
            </ul>
          </div>

          <div>
            <h3 class="font-bold mb-4 text-blue-400">Expertise</h3>
            <ul class="space-y-2 text-gray-400 text-sm">
              <li>PME-PMI</li>
              <li>Collectivités</li>
              <li>Pôles & Clusters</li>
            </ul>
          </div>

          <div>
            <h3 class="font-bold mb-4 text-blue-400">Contact</h3>
            <p class="text-gray-400 text-sm">+ (226) 2540 9771</p>
            <p class="text-gray-400 text-sm">stratactionconsulting@gmail.com</p>
          </div>
        </div>
        
        <div class="border-t border-gray-700 mt-8 pt-8 text-center">
          <p class="text-gray-400 text-sm">
            © 2024 StratAction Consulting. Tous droits réservés.
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.8s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.zoom-fade-enter-active,
.zoom-fade-leave-active {
  transition: opacity 1s ease, transform 1.4s ease;
  position: absolute;
  inset: 0;
}

.zoom-fade-enter-from {
  opacity: 0;
  transform: scale(1.05);
}

.zoom-fade-leave-to {
  opacity: 0;
  transform: scale(1.05);
}

@keyframes spin-slow {
  from { transform: rotate(0); }
  to { transform: rotate(360deg); }
}

.animate-spin-very-slow {
  animation: spin-slow 6s linear infinite;
}
</style>
