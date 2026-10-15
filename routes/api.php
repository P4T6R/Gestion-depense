<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CategorieDepenseController;
use App\Http\Controllers\API\ChantierController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\DepenseController;
use App\Http\Controllers\API\PaiementController;
use App\Http\Controllers\API\UniteMesureController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\Web\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Plateforme Suivi Dépenses Chantier
|--------------------------------------------------------------------------
*/

// ========== ROUTES PUBLIQUES ==========
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// ========== ROUTES PROTÉGÉES (Authentification requise) ==========
Route::middleware(['auth:sanctum', 'active'])->group(function () {

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read']);

    Route::get('/justificatifs/{justificatif}', \App\Http\Controllers\JustificatifController::class)->name('api.justificatifs.show');

    // ---------- AUTHENTIFICATION ----------
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
    });

    // ---------- DASHBOARD ----------
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/stats-mois', [DashboardController::class, 'statsParMois']);
    });

    // ---------- UTILISATEURS (Admin uniquement) ----------
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/chefs-chantier', [UserController::class, 'chefsChantier']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::patch('/{user}/toggle-active', [UserController::class, 'toggleActive']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
    });

    // ---------- CHANTIERS ----------
    Route::prefix('chantiers')->group(function () {
        Route::get('/', [ChantierController::class, 'index']);
        Route::post('/', [ChantierController::class, 'store']);
        Route::get('/{chantier}', [ChantierController::class, 'show']);
        Route::put('/{chantier}', [ChantierController::class, 'update']);
        Route::delete('/{chantier}', [ChantierController::class, 'destroy']);

        // Gestion des chefs de chantier
        Route::post('/{chantier}/affecter-chef', [ChantierController::class, 'affecterChef']);
        Route::delete('/{chantier}/retirer-chef/{chef}', [ChantierController::class, 'retirerChef']);

        // Statistiques du chantier
        Route::get('/{chantier}/statistiques', [ChantierController::class, 'statistiques']);
    });

    // ---------- DÉPENSES ----------
    Route::prefix('depenses')->group(function () {
        Route::get('/', [DepenseController::class, 'index']);
        Route::post('/', [DepenseController::class, 'store']);
        Route::post('/avec-lignes', [DepenseController::class, 'storeAvecLignes']); // Nouvelle route pour facture multi-lignes
        Route::get('/statistiques', [DepenseController::class, 'statistiques']);
        Route::get('/{depense}', [DepenseController::class, 'show']);
        Route::put('/{depense}', [DepenseController::class, 'update']);
        Route::delete('/{depense}', [DepenseController::class, 'destroy']);

        Route::post('/{depense}/approve', [DepenseController::class, 'approve']);

        // Validation comptable
        Route::post('/{depense}/valider', [DepenseController::class, 'valider']);
        Route::post('/{depense}/rejeter', [DepenseController::class, 'rejeter']);
        Route::post('/{depense}/paiements', [PaiementController::class, 'store']);
        Route::post('/{depense}/paiements/{paiement}/annuler', [PaiementController::class, 'cancel']);
    });

    // ---------- CATÉGORIES DE DÉPENSES ----------
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategorieDepenseController::class, 'index']);
        Route::post('/', [CategorieDepenseController::class, 'store']);
        Route::get('/{categorie}', [CategorieDepenseController::class, 'show']);
        Route::put('/{categorie}', [CategorieDepenseController::class, 'update']);
        Route::patch('/{categorie}/toggle-active', [CategorieDepenseController::class, 'toggleActive']);
        Route::delete('/{categorie}', [CategorieDepenseController::class, 'destroy']);
    });

    // ---------- UNITÉS DE MESURE ----------
    Route::prefix('unites')->group(function () {
        Route::get('/', [UniteMesureController::class, 'index']);
        Route::post('/', [UniteMesureController::class, 'store']);
        Route::get('/{unite}', [UniteMesureController::class, 'show']);
        Route::put('/{unite}', [UniteMesureController::class, 'update']);
        Route::patch('/{unite}/toggle-active', [UniteMesureController::class, 'toggleActive']);
        Route::delete('/{unite}', [UniteMesureController::class, 'destroy']);
    });
});
