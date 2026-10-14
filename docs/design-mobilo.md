# Design Mobilo : où se trouve quoi

## Page d'accueil publique (`/`)
- Contrôleur : `HomeController::landing()` renvoie `HomeController::vitrine()`.
  L'ancienne page reste disponible dans `legacyLanding()`.
- Vues : `resources/views/framer/layout.blade.php` (en-tête, pied de page) et `framer/home.blade.php` (sections).
- Style : `public/css/framer-copy.css`. Images : `public/landing/framer/`.
- Les forfaits sont lus en base. Les avis sont des exemples de démonstration à remplacer.
- `/vitrine` donne la même page (adresse de travail).

## Logiciel (administration, caisse, superadmin, connexion, site client)
- `public/css/jimbe-theme.css` : thème global (barre flottante, îlot latéral, cartes, boutons, champs, tableaux, fenêtres, mode sombre, connexion à deux volets, bandeau du tableau de bord).
- `public/css/jimbe-brand.css` : couleur de marque (vert `#055049`, éclairci en mode sombre). Chargé uniquement dans l'administration, la connexion et la livraison, pas dans le site client (qui garde la couleur du restaurant).
- `resources/views/sections/jimbe_theme.blade.php` : inclusion commune, appelée par les gabarits `layouts/app`, `auth`, `public`, `guest`, `delivery-portal`.
- `resources/views/layouts/auth.blade.php` : connexion à deux volets (panneau vert avec illustrations).
- `resources/views/dashboard/index.blade.php` : bandeau d'accueil du tableau de bord.

## Réglages modifiés
- Couleur par défaut : `#055049` (`5, 80, 73`) dans la base locale (restaurants et paramètres globaux) et dans les seeders.
- Nom du logiciel : « Jimbe ».
- Logo par défaut : `public/img/mobilo-mark.svg` (voir `GlobalSetting::logoUrl`).
- Icônes d'onglet 16 et 32 px et `favicon.ico` remplacées. Restent à refaire : `android-chrome-192x192.png`, `android-chrome-512x512.png`, `apple-touch-icon.png` (ancien « T » violet).
