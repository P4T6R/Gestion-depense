# Gestion Dépenses Terrain

Application Flutter Android/iOS réservée aux chefs de chantier. Elle utilise l'API Laravel du dépôt et garde les mêmes règles d'accès, de rejet, de doublons et de validation.

## Parcours

- Connexion avec un compte `chef_chantier` actif, puis choix parmi les chantiers attribués.
- Accueil avec factures récentes et états « chez le directeur », « chez la comptable », « validée » ou « à corriger ».
- Recherche et filtres des factures, détail avec aperçu des images et PDF protégés.
- Photo par la caméra native ou depuis la galerie ; reconnaissance du texte sur l'appareil avec Google ML Kit (alphabet latin).
- Vérification manuelle du fournisseur, du numéro, de la date et du total TTC. L'objet, la catégorie et l'unité sont requis par l'API. La photo est toujours jointe à une nouvelle facture.
- Correction d'une facture rejetée, avec possibilité de joindre une nouvelle photo. Les factures à plusieurs lignes conservent leurs lignes modifiables.

L'OCR propose des valeurs, mais ne valide pas la facture à la place de l'utilisateur. Le montant n'est proposé que si une ligne « Total » ou « Net à payer » est reconnue. L'API effectue les contrôles définitifs, notamment sur les doublons. L'envoi demande une connexion réseau ; en cas d'échec, le formulaire reste ouvert pour réessayer.

## Démarrer

Installer une version récente du SDK Flutter et les outils Android ; pour iOS, utiliser macOS avec Xcode et CocoaPods. Depuis ce dossier :

```bash
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000
```

`10.0.2.2` désigne l'hôte local depuis l'émulateur Android. Sur le simulateur iOS, utiliser `http://localhost:8000`. Sur un appareil physique, fournir une URL HTTPS accessible par le téléphone. Ne pas placer de secret dans `API_BASE_URL`. Démarrer d'abord l'API Laravel et créer/affecter un compte chef de chantier. Le chemin `/api` est ajouté automatiquement par l'application.

```bash
flutter analyze
flutter test
```

Android : API minimale 24, Internet autorisé ; HTTP en clair uniquement dans la variante `debug` pour l'émulateur local. iOS : cible 15.5, accès caméra et galerie déclaré, trafic local autorisé. Utiliser HTTPS pour toute distribution réelle.

## Choix techniques

L'interface s'appuie sur les composants Material 3 natifs de Flutter : navigation basse, grandes cibles tactiles, états écrits et icônes homogènes. La palette se limite au vert profond, à l'encre sombre et à l'ambre pour les corrections. Les champs préremplis restent modifiables et la photo est visible avant la soumission.

Le scan utilise [`image_picker`](https://pub.dev/packages/image_picker) pour la caméra/galerie et [`google_mlkit_text_recognition`](https://pub.dev/packages/google_mlkit_text_recognition) pour la reconnaissance locale. Les jetons Sanctum sont stockés avec [`flutter_secure_storage`](https://pub.dev/packages/flutter_secure_storage). Les PDF protégés sont ouverts depuis l'API avec [`pdfrx`](https://pub.dev/packages/pdfrx). Ces packages et leurs contraintes de plateforme ont été vérifiés sur pub.dev.
