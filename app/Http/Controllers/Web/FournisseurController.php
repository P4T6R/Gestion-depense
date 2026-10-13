<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Fournisseur;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FournisseurController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $fournisseurs = Fournisseur::query()->withCount('depenses')
            ->when($search !== '', fn ($query) => $query->where('nom', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->orderBy('nom')->paginate(20)->withQueryString();

        return view('fournisseurs.index', compact('fournisseurs', 'search'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['nom_normalise'] = $this->normalizedName($data['nom']);
        if (Fournisseur::where('nom_normalise', $data['nom_normalise'])->exists()) {
            throw ValidationException::withMessages(['nom' => 'Ce fournisseur existe déjà.']);
        }
        Fournisseur::create($data);

        return to_route('fournisseurs.index')->with('success', 'Fournisseur créé.');
    }

    public function update(Request $request, Fournisseur $fournisseur)
    {
        $data = $this->validated($request);
        $data['nom_normalise'] = $this->normalizedName($data['nom']);
        if (Fournisseur::where('nom_normalise', $data['nom_normalise'])->whereKeyNot($fournisseur->id)->exists()) {
            throw ValidationException::withMessages(['nom' => 'Ce fournisseur existe déjà.']);
        }
        $fournisseur->update($data);

        return to_route('fournisseurs.index')->with('success', 'Fournisseur mis à jour.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', Rule::in(['0', '1'])],
        ]);
    }

    private function normalizedName(string $name): string
    {
        $normalized = Fournisseur::normaliser($name);
        if ($normalized === '') {
            throw ValidationException::withMessages(['nom' => 'Saisissez un nom de fournisseur valide.']);
        }

        return $normalized;
    }
}
