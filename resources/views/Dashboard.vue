<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: Object,
    recentEnrollments: Array,
    popularCourses: Array,
});
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout>
        <!-- Statistiques -->
        <div class="grid md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Utilisateurs</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.total_users }}</p>
                    </div>
                    <div class="bg-blue-100 p-4 rounded-xl">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Cours</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.total_courses }}</p>
                    </div>
                    <div class="bg-blue-100 p-4 rounded-xl">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Inscriptions</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.total_enrollments }}</p>
                    </div>
                    <div class="bg-green-100 p-4 rounded-xl">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-purple-500 hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Quiz Complétés</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.total_quizzes }}</p>
                    </div>
                    <div class="bg-purple-100 p-4 rounded-xl">
                        <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Dernières inscriptions -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Dernières Inscriptions</h2>
                    <Link :href="route('admin.courses')" class="text-blue-500 hover:text-blue-600 font-semibold text-sm">
                        Voir tout
                    </Link>
                </div>

                <div class="space-y-4">
                    <div v-for="enrollment in recentEnrollments" :key="enrollment.id" class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold mr-4">
                                {{ enrollment.user.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">{{ enrollment.user.name }}</p>
                                <p class="text-sm text-gray-600">{{ enrollment.course.titre }}</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500">{{ new Date(enrollment.created_at).toLocaleDateString('fr-FR') }}</span>
                    </div>
                </div>
            </div>

            <!-- Cours populaires -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Cours Populaires</h2>
                    <Link :href="route('admin.courses')" class="text-blue-500 hover:text-blue-600 font-semibold text-sm">
                        Voir tout
                    </Link>
                </div>

                <div class="space-y-4">
                    <div v-for="course in popularCourses" :key="course.id" class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition">
                        <div class="flex items-center flex-1">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">{{ course.titre }}</p>
                                <p class="text-sm text-gray-600">{{ course.categorie }}</p>
                            </div>
                        </div>
                        <div class="text-right ml-4">
                            <p class="font-bold text-blue-500">{{ course.enrollments_count }}</p>
                            <p class="text-xs text-gray-500">inscrits</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="mt-8 grid md:grid-cols-3 gap-6">
            <Link :href="route('admin.courses.create')" class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl p-8 hover:shadow-2xl hover:scale-105 transition-all group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold mb-2">Créer un Cours</h3>
                        <p class="text-blue-100">Ajouter un nouveau cours à la plateforme</p>
                    </div>
                    <svg class="w-12 h-12 text-white/50 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
            </Link>

            <Link :href="route('admin.users')" class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl p-8 hover:shadow-2xl hover:scale-105 transition-all group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold mb-2">Gérer les Utilisateurs</h3>
                        <p class="text-blue-100">Consulter et gérer tous les utilisateurs</p>
                    </div>
                    <svg class="w-12 h-12 text-white/50 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </Link>

            <Link :href="route('admin.courses')" class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-2xl p-8 hover:shadow-2xl hover:scale-105 transition-all group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold mb-2">Voir les Cours</h3>
                        <p class="text-green-100">Gérer tous les cours de la plateforme</p>
                    </div>
                    <svg class="w-12 h-12 text-white/50 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
            </Link>
        </div>
    </AdminLayout>
</template>