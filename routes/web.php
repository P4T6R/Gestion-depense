<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\CategorieDepenseController;
use App\Http\Controllers\Web\ChantierController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DepenseController;
use App\Http\Controllers\Web\UniteMesureController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::get('/login', [AuthController::class, 'showLogin'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware(['guest', 'throttle:login']);

// Routes protégées par authentification web
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/justificatifs/{justificatif}', \App\Http\Controllers\JustificatifController::class)->name('justificatifs.show');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // ========== UTILISATEURS (Admin seulement) ==========
    Route::middleware('checkRole:administrateur')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::get('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::put('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.update-password');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // ========== CHANTIERS (Admin, Chef de chantier, Directeur) ==========
    // Admin seulement peut créer, modifier, supprimer
    Route::middleware('checkRole:administrateur')->group(function () {
        Route::get('/chantiers/create', [ChantierController::class, 'create'])->name('chantiers.create');
        Route::post('/chantiers', [ChantierController::class, 'store'])->name('chantiers.store');
        Route::get('/chantiers/{chantier}/edit', [ChantierController::class, 'edit'])->name('chantiers.edit');
        Route::put('/chantiers/{chantier}', [ChantierController::class, 'update'])->name('chantiers.update');
        Route::delete('/chantiers/{chantier}', [ChantierController::class, 'destroy'])->name('chantiers.destroy');
    });

    // Tous (sauf comptable) peuvent consulter
    Route::middleware('checkRole:administrateur,chef_chantier,directeur')->group(function () {
        Route::get('/chantiers', [ChantierController::class, 'index'])->name('chantiers.index');
        Route::get('/chantiers/{chantier}', [ChantierController::class, 'show'])->name('chantiers.show');
    });

    // ========== DÉPENSES ==========
    // Tous les utilisateurs peuvent voir les dépenses (filtrées par leur rôle)
    Route::get('/depenses', [DepenseController::class, 'index'])->name('depenses.index');

    // Chef de chantier et Directeur peuvent créer, modifier, supprimer des dépenses
    Route::middleware('checkRole:chef_chantier,directeur')->group(function () {
        Route::get('/depenses/create', [DepenseController::class, 'create'])->name('depenses.create');
        Route::post('/depenses', [DepenseController::class, 'store'])->name('depenses.store');
        Route::get('/depenses/{depense}/edit', [DepenseController::class, 'edit'])->name('depenses.edit');
        Route::put('/depenses/{depense}', [DepenseController::class, 'update'])->name('depenses.update');
        Route::delete('/depenses/{depense}', [DepenseController::class, 'destroy'])->name('depenses.destroy');
    });

    Route::get('/depenses/{depense}', [DepenseController::class, 'show'])->name('depenses.show');

    // Le directeur décide sur les factures en attente.
    Route::middleware('checkRole:directeur')->group(function () {
        Route::post('/depenses/{depense}/approve', [DepenseController::class, 'approve'])->name('depenses.approve');
        Route::post('/depenses/{depense}/reject', [DepenseController::class, 'reject'])->name('depenses.reject');
    });

    // Comptable peut valider ou rejeter les dépenses
    Route::middleware('checkRole:comptable')->group(function () {
        Route::post('/depenses/{depense}/validate', [DepenseController::class, 'validateDepense'])->name('depenses.validate');
    });

    // ========== RAPPORTS ET EXPORTS (Admin et Comptable) ==========
    Route::middleware('checkRole:administrateur,comptable')->group(function () {
        Route::get('/depenses/rapports/index', [DepenseController::class, 'rapports'])->name('depenses.rapports');
        Route::get('/depenses/rapports/export-pdf', [DepenseController::class, 'exportPdf'])->name('depenses.export.pdf');
        Route::get('/depenses/rapports/export-excel', [DepenseController::class, 'exportExcel'])->name('depenses.export.excel');
        Route::get('/depenses/rapports/export-csv', [DepenseController::class, 'exportCsv'])->name('depenses.export.csv');
    });

    // ========== CATÉGORIES DE DÉPENSES (Admin seulement) ==========
    Route::middleware('checkRole:administrateur')->group(function () {
        Route::get('/categories', [CategorieDepenseController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [CategorieDepenseController::class, 'create'])->name('categories.create');
        Route::post('/categories', [CategorieDepenseController::class, 'store'])->name('categories.store');
        Route::get('/categories/{categorie}', [CategorieDepenseController::class, 'show'])->name('categories.show');
        Route::get('/categories/{categorie}/edit', [CategorieDepenseController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{categorie}', [CategorieDepenseController::class, 'update'])->name('categories.update');
        Route::post('/categories/{categorie}/toggle-active', [CategorieDepenseController::class, 'toggleActive'])->name('categories.toggle-active');
        Route::delete('/categories/{categorie}', [CategorieDepenseController::class, 'destroy'])->name('categories.destroy');
    });

    // ========== UNITÉS DE MESURE (Admin seulement) ==========
    Route::middleware('checkRole:administrateur')->group(function () {
        Route::get('/unites', [UniteMesureController::class, 'index'])->name('unites.index');
        Route::get('/unites/create', [UniteMesureController::class, 'create'])->name('unites.create');
        Route::post('/unites', [UniteMesureController::class, 'store'])->name('unites.store');
        Route::get('/unites/{unite}', [UniteMesureController::class, 'show'])->name('unites.show');
        Route::get('/unites/{unite}/edit', [UniteMesureController::class, 'edit'])->name('unites.edit');
        Route::put('/unites/{unite}', [UniteMesureController::class, 'update'])->name('unites.update');
        Route::post('/unites/{unite}/toggle-active', [UniteMesureController::class, 'toggleActive'])->name('unites.toggle-active');
        Route::delete('/unites/{unite}', [UniteMesureController::class, 'destroy'])->name('unites.destroy');
    });
});
