# Création de Dépense avec Lignes Multiples

## Problème résolu
Avant, il fallait créer une dépense par article (ciment, carreau, sable = 3 dépenses distinctes).
Maintenant, **1 facture = 1 dépense avec plusieurs lignes d'articles**.

## Structure de la base de données

### Table `depenses`
- Infos générales : numéro facture, fournisseur, date, statuts
- Montant total recalculé automatiquement depuis les lignes

### Table `lignes_depense`
- Détails par article : désignation, quantité, prix unitaire, catégorie, unité

## Migration sur le serveur

```bash
# 1. Transférer les fichiers de migration
scp database/migrations/2026_02_12_000000_create_lignes_depense_table.php root@72.62.178.54:/var/www/suivi-depense/database/migrations/
scp database/migrations/2026_02_12_000001_add_facture_fields_to_depenses_table.php root@72.62.178.54:/var/www/suivi-depense/database/migrations/

# 2. Transférer le modèle LigneDepense
scp app/Models/LigneDepense.php root@72.62.178.54:/var/www/suivi-depense/app/Models/

# 3. Transférer les modèles et contrôleurs mis à jour
scp app/Models/Depense.php root@72.62.178.54:/var/www/suivi-depense/app/Models/
scp app/Http/Controllers/API/DepenseController.php root@72.62.178.54:/var/www/suivi-depense/app/Http/Controllers/API/
scp routes/api.php root@72.62.178.54:/var/www/suivi-depense/routes/

# 4. Sur le serveur, exécuter les migrations
php artisan migrate --force
php artisan optimize:clear
sudo systemctl restart php8.3-fpm
```

## Utilisation de l'API

### Endpoint
```
POST /api/depenses/avec-lignes
```

### Headers
```
Authorization: Bearer {token}
Content-Type: application/json
```

### Exemple 1 : Facture quincaillerie (ciment + carreaux + sable)

```json
{
  "chantier_id": 1,
  "numero_facture": "FACT-2026-001",
  "fournisseur": "Quincaillerie ABC",
  "date_facture": "2026-02-12",
  "statut_paiement": "non_paye",
  "statut_livraison": "livre",
  "observations": "Livraison du matin - Chantier Centre-Ville",
  "lignes": [
    {
      "designation": "Ciment CPJ 35",
      "categorie_depense_id": 1,
      "unite_mesure_id": 2,
      "quantite": 50,
      "prix_unitaire": 6500,
      "observations": "Livré en bon état"
    },
    {
      "designation": "Carreaux 30x30 cm",
      "categorie_depense_id": 1,
      "unite_mesure_id": 8,
      "quantite": 120,
      "prix_unitaire": 1500,
      "observations": null
    },
    {
      "designation": "Sable de rivière",
      "categorie_depense_id": 1,
      "unite_mesure_id": 4,
      "quantite": 10,
      "prix_unitaire": 25000,
      "observations": "Qualité premium"
    }
  ]
}
```

**Total automatique** : (50 × 6500) + (120 × 1500) + (10 × 25000) = **855 000 FCFA**

### Exemple 2 : Dépense administrative

```json
{
  "chantier_id": null,
  "numero_facture": "DAO-2026-045",
  "fournisseur": "Direction des Marchés Publics",
  "date_facture": "2026-02-12",
  "statut_paiement": "paye",
  "statut_livraison": "livre",
  "observations": "Appel d'offre Route Banfora",
  "lignes": [
    {
      "designation": "Achat dossier d'appel d'offre",
      "categorie_depense_id": 9,
      "quantite": 1,
      "prix_unitaire": 75000,
      "observations": "DAO Route Banfora"
    },
    {
      "designation": "Frais d'enregistrement",
      "categorie_depense_id": 11,
      "quantite": 1,
      "prix_unitaire": 15000,
      "observations": "Timbres + enregistrement"
    }
  ]
}
```

**Total automatique** : 90 000 FCFA

### Réponse API (Success)

```json
{
  "message": "Dépense créée avec succès",
  "depense": {
    "id": 15,
    "chantier_id": 1,
    "user_id": 4,
    "numero_facture": "FACT-2026-001",
    "fournisseur": "Quincaillerie ABC",
    "date_facture": "2026-02-12",
    "prix_total": 855000.00,
    "statut_paiement": "non_paye",
    "statut_livraison": "livre",
    "statut_validation": "en_attente",
    "observations": "Livraison du matin - Chantier Centre-Ville",
    "created_at": "2026-02-12T10:30:00.000000Z",
    "lignes": [
      {
        "id": 1,
        "depense_id": 15,
        "designation": "Ciment CPJ 35",
        "categorie_depense_id": 1,
        "unite_mesure_id": 2,
        "quantite": 50.00,
        "prix_unitaire": 6500.00,
        "montant": 325000.00,
        "categorie_depense": {
          "id": 1,
          "nom": "Matériaux construction",
          "type": "chantier"
        },
        "unite_mesure": {
          "id": 2,
          "nom": "Sac",
          "code": "SAC"
        }
      },
      {
        "id": 2,
        "depense_id": 15,
        "designation": "Carreaux 30x30 cm",
        "quantite": 120.00,
        "prix_unitaire": 1500.00,
        "montant": 180000.00
      },
      {
        "id": 3,
        "depense_id": 15,
        "designation": "Sable de rivière",
        "quantite": 10.00,
        "prix_unitaire": 25000.00,
        "montant": 250000.00
      }
    ]
  }
}
```

## Règles de validation

| Champ | Obligatoire | Type | Règles |
|-------|-------------|------|--------|
| `chantier_id` | ✅ Oui | Integer | Doit exister |
| `numero_facture` | ❌ Non | String | Max 255 caractères |
| `fournisseur` | ❌ Non | String | Max 255 caractères |
| `date_facture` | ❌ Non | Date | Format: YYYY-MM-DD |
| `lignes` | ✅ Oui | Array | Minimum 1 ligne |
| `lignes.*.designation` | ✅ Oui | String | Max 255 caractères |
| `lignes.*.quantite` | ✅ Oui | Decimal | Min: 0.01 |
| `lignes.*.prix_unitaire` | ✅ Oui | Decimal | Min: 0 |
| `lignes.*.categorie_depense_id` | ❌ Non | Integer | Doit exister |
| `lignes.*.unite_mesure_id` | ❌ Non | Integer | Doit exister |

## Compatibilité

L'ancienne méthode `POST /api/depenses` (1 produit par dépense) **continue de fonctionner**.

Les nouvelles dépenses multi-lignes et les anciennes dépenses mono-produit **cohabitent** dans la même base.

## Frontend : Formulaire recommandé

```html
<!-- Bouton pour ajouter une ligne -->
<button @click="ajouterLigne">+ Ajouter un article</button>

<!-- Liste des lignes -->
<div v-for="(ligne, index) in lignes" :key="index">
  <input v-model="ligne.designation" placeholder="Désignation">
  <select v-model="ligne.categorie_depense_id">
    <option :value="cat.id" v-for="cat in categories">{{ cat.nom }}</option>
  </select>
  <input v-model.number="ligne.quantite" type="number" placeholder="Quantité">
  <input v-model.number="ligne.prix_unitaire" type="number" placeholder="Prix unitaire">
  <span>{{ ligne.quantite * ligne.prix_unitaire }} FCFA</span>
  <button @click="retirerLigne(index)">🗑️</button>
</div>

<!-- Total calculé -->
<div>
  <strong>TOTAL : {{ totalFacture }} FCFA</strong>
</div>
```

## Avantages

✅ **UX améliorée** : 1 facture = 1 saisie (au lieu de 3-4 saisies)  
✅ **Cohérence** : Toutes les lignes partagent le même numéro de facture, fournisseur, date  
✅ **Justificatif unique** : 1 photo de facture pour tous les articles  
✅ **Validation groupée** : Approuver/rejeter toute la facture d'un coup  
✅ **Rétro-compatible** : Les anciennes dépenses continuent de fonctionner
