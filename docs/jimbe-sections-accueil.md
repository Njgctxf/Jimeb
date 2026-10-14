# Sections de l'accueil Jimbe à intégrer dans la nouvelle page d'accueil

Objectif : copier d'abord le site Framer (Mobilo) à l'identique, puis y réintégrer
les sections propres à Jimbe listées ci-dessous. Source : `resources/views/landing/_home.blade.php`
et `app/Http/Controllers/HomeController.php` (méthode `landing`).

## Sections de la page d'accueil actuelle (contenu piloté par l'administration)

| # | Section | Données | Où se modifie le contenu |
|---|---|---|---|
| 1 | **Hero** : titre, sous-titre, bouton d'essai gratuit (« Essai gratuit de X jours » si un forfait d'essai existe, sinon « Commencer »), capture du tableau de bord | `FrontDetail` : `header_title`, `header_description`, `image_url` ; `$trialPackage` | Admin → Site vitrine |
| 2 | **Fonctionnalités clés (avec image)** : titre de section, puis cartes titre + description + image | `FrontFeature` (type `image`), `feature_with_image_heading` | Admin → Site vitrine |
| 3 | **Pourquoi Jimbe (fonctionnalités à icônes)** : grille de cartes icône + titre + texte. Valeurs par défaut : Menu QR, Intégration de paiement, Gestion du personnel, Caisse (POS), Plans de salle, Bons de cuisine (KOT), Impression des additions, Rapports | `FrontFeature` (type `icon`), `feature_with_icon_heading` | Admin → Site vitrine |
| 4 | **Témoignages** : avis (texte, nom, poste) | `FrontReviewSetting`, `review_heading` | Admin → Site vitrine |
| 5 | **Tarifs** : forfaits issus de la base, bascule mensuel/annuel, prix à vie, liste des modules avec limites (articles de menu, commandes par jour, personnel) | `Package`, `PackageModule`, `monthlyPackages`, `annualPackages`, `lifetimePackages` | Admin → Forfaits |
| 6 | **FAQ** : questions et réponses | `FrontFaq`, `faq_heading` | Admin → Site vitrine |
| 7 | **Contact** : société, adresse, e-mail, image | `Contact` | Admin → Site vitrine |

## Éléments transverses à conserver

- **En-tête** : liens Accueil / Fonctionnalités / Tarifs / FAQ / Contact, menus personnalisés (`CustomMenu`, position `header`), connexion / tableau de bord, bouton d'inscription, sélecteur de langue.
- **Pied de page** : menus personnalisés (position `footer`), liens sociaux (Facebook, Instagram, X, Yelp, Google), sélecteur de langue, mentions de droits.
- **Routes** : inscription restaurant `restaurant_signup`, connexion `login`, pages `customMenu`.
- **Mode du site** : `landing_type` (`static` ou `dynamic`) et `landing_site_type` (`theme` ou `custom`) ; le type `custom` renvoie un fichier HTML externe.

## Sections propres à Jimbe à ajouter en plus de la copie Mobilo

1. Tarifs réels (forfaits Jimbe, bascule mensuel/annuel, modules et limites) à la place de la grille Mobilo.
2. Fonctionnalités restaurant (menu QR, commandes, réservations, caisse, cuisine) à la place des fonctionnalités « gestion de tâches ».
3. Témoignages, FAQ et contact pilotés par l'administration.
4. Essai gratuit lié au forfait d'essai.
5. Pied de page avec menus personnalisés et réseaux sociaux.
6. Plus tard : paiements Mobile Money, mode de service assisté/autonome (voir le brief produit).
