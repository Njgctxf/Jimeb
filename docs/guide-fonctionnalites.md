# Guide des fonctionnalités et plan de test

Ce guide est établi **à partir du code, des routes et de la base de démonstration**. Je n'ai pas encore testé chaque flux à la main : les points marqués « à confirmer » sont ceux à vérifier en testant.

## 0. Pour tester

**Adresse locale :** `http://resto-saas.test` (Herd + MySQL de Laragon démarré).

| Compte (démonstration) | Mot de passe | Rôle | Ouvre |
|---|---|---|---|
| `superadmin@example.com` | `123456` | Super admin (vous, la plateforme) | `/super-admin-dashboard` |
| `admin@example.com` | `123456` | Admin du « Demo Restaurant » | `/dashboard` |
| `waiter@example.com` (et waiter2 à 5) | `123456` | Serveur | `/dashboard` (menu réduit) |

Rôles existants : **Super Admin, Admin, Branch Head (chef d'établissement), Chef (cuisine), Waiter (serveur)**. Les droits par rôle se règlent dans Réglages → Rôles.

**Données de démonstration :** 5 restaurants, 30 tables, 45 plats, 69 commandes, 75 clients, 36 réservations. L'essai gratuit affiche « 30 days left on trial ».

**Forfaits en base :** Subscription Package (10 $/mois, 100 $/an), Life Time (199 $ une fois), Private Package (5 $/mois), Trial Package, Default. Chaque forfait active des **modules** : Area, Customer, Delivery Executive, Expense, Item Category, KOT, Menu, Menu Item, Order, Payment, Report, Reservation, Settings, Staff, Table, Waiter Request. Un module non inclus dans le forfait masque les pages correspondantes.

## 1. Les cinq « espaces » du logiciel

1. **Site public** (`/`) : la page vitrine, l'inscription d'un restaurant (`/restaurant-signup`) et la connexion (`/login`).
2. **Site client / menu QR** (`/restaurant/{identifiant}`, ex. `/restaurant/demo-restaurant`) : ce que voient les clients.
3. **Espace restaurant** (connecté en Admin, Chef d'établissement, Chef, Serveur) : l'exploitation au quotidien.
4. **Espace super admin** : gestion de la plateforme (restaurants, forfaits, facturation).
5. **Portail livreur** (`/delivery/login`) : connexion par code (OTP) pour les livreurs.

---

## 2. Espace restaurant : page par page

### Tableau de bord (`/dashboard`)
Vue du jour : bandeau d'accueil avec raccourcis, commandes du jour, recettes du jour, clients du jour, recette quotidienne moyenne du mois avec comparaison, graphique des ventes du mois, liste des commandes du jour, et d'autres blocs (plats les plus vendus, etc.).
**À tester :** les chiffres changent après une commande payée ; les raccourcis (Nouvelle commande, Tables, Réservations) ouvrent les bons écrans ; le sélecteur d'établissement en haut du menu change les chiffres.

### Menu
- **Menus** (`/menus`) : les cartes (ex. « Indo-Chinese Fusion », « North Indian Delights »). Ajout, modification, suppression, **tri** (`menu-items/sort-entities`).
- **Articles** (`/menu-items`) : liste des plats (nom, prix, catégorie, menu, disponible, visible sur le site client). Boutons : *Show Filters*, **Add Menu Item**, **Organize Items** (tri), **Export Menu Items** (Excel/CSV), **Bulk Upload** (`menu-items/bulk-import`), **Copy between branches** (copier le menu vers d'autres établissements). Un plat peut avoir des **variantes** (tailles), des **modificateurs**, des **taxes**, une photo, des allergènes (réglage UE), végé / non-végé, un temps de préparation.
- **Catégories** (`/item-categories`), **Groupes de modificateurs** (`/modifier-groups`), **Modificateurs** (`/item-modifiers`) : options payantes ou gratuites (suppléments, cuisson…).
**À tester :** créer un plat avec variante + modificateur, le rendre indisponible (case « Is available »), et vérifier qu'il disparaît du site client ; importer un fichier en masse ; exporter.

### Tables
- **Zones** (`/areas`) : regroupements (salle, terrasse…).
- **Tables** (`/tables`) : état de chaque table (libre / occupée), **verrou** (une table ouverte par un serveur est verrouillée, déverrouillage forcé possible), **serveur assigné**, **QR code** par table. Ouvre le détail d'une commande de table ou crée une nouvelle commande de cuisine (KOT).
- **QR codes** (`/qr-codes`) : génération et téléchargement des QR codes par table, pour imprimer.
**À tester :** scanner (ou ouvrir) le lien d'une table `/restaurant/table/{hash}`, passer une commande, voir la table passer « occupée » ; déverrouiller une table.

### Appels serveur (`/waiter-requests`)
Les clients appellent un serveur depuis le site QR (« Call Waiter »). La page liste les demandes à traiter.
**À tester :** appeler le serveur depuis le site client, puis marquer la demande comme traitée.

### Réservations (`/reservations`)
Liste et suivi des réservations (date, heure, nombre de couverts, client, statut), **assignation / changement de table**, modification. Les clients réservent depuis le site client (`Book a Table`). Réglages dédiés : jours, créneaux, capacité (Réglages → Réservations).
**À tester :** réserver côté client, la voir côté restaurant, lui assigner une table.

### Caisse / POS (`/pos`)
Le cœur du service. Fonctions identifiées :
- choix du **type de commande** (sur place, livraison, à emporter) et de la **table** ; **fusion de tables** et changement de table ;
- catalogue avec recherche, **filtres par menu et catégorie**, variantes et modificateurs, notes par article ;
- panier : quantités, suppression, **remises**, frais supplémentaires, taxes ;
- client : choix ou création ; **fidélité** (points, rachat : présente dans le code, à confirmer si activée) ;
- **livraison** : attribution d'un livreur ;
- **Save & Print KOT** (bon de cuisine), **Bill / Print** (addition), brouillon (« Save as Draft »), paiement rapide ;
- écran client (`/customer-display`) qui affiche la commande en cours.
**À tester :** un parcours complet « sur place » (table → articles → KOT → paiement) puis « livraison » avec un livreur.

### Commandes (`/orders`)
Liste filtrable par date, détail d'une commande, **changement de statut**, ajout / suppression d'articles, remises, frais, **paiement** (espèces, carte…, **paiement fractionné** par personne ou par articles, **pourboire**), **annulation**, **fusion de commandes**, création d'un KOT, impression / PDF de l'addition (`orders/print/{id}`, `orders/pdf/{id}`), reçus fractionnés.
**Commandes récentes** (`/orders/recent`).
**Statuts d'une commande :** placée → confirmée → en préparation → prête → servie / remise au livreur → en livraison → livrée → terminée, ou annulée.
**À tester :** payer en deux parts, ajouter un pourboire, annuler une commande, imprimer l'addition.

### Cuisine / KOT (`/kots`)
Écran de cuisine : les bons arrivent en temps réel, on **fait avancer le statut** du bon ou de chaque article, on peut réduire une quantité, supprimer, ré-imprimer. Les états exacts (en attente, en cuisine, prêt…) sont à confirmer en testant. Les réglages (Réglages → KOT) définissent les postes (cuisine, bar) et les motifs d'annulation.
**À tester :** passer une commande en POS, la voir arriver en cuisine, la passer « prête ».

### Écrans d'affichage
`/customer-display` (écran client côté caisse) et `/customer-order-board` (tableau des commandes pour la salle).

### Clients (`/customers`)
Base clients : coordonnées, adresses, historique de commandes. Ajout manuel, ou création automatique à la commande.
**À tester :** ouvrir un client et voir ses commandes.

### Personnel (`/staff`)
Comptes du personnel (admin, chef d'établissement, chef, serveur) avec leur rôle et leur établissement. Création, modification, suppression. Les limites dépendent du forfait.
**À tester :** créer un serveur, se connecter avec ce compte et constater les droits restreints.

### Livreurs (`/delivery-executives`)
Liste des livreurs, **suivi de la livraison** (position / itinéraire d'une commande), et **suivi de l'argent** à la livraison (`/delivery-executives/cash-monitoring`, règlement des espèces collectées).
**Portail livreur** (`/delivery/login`, code OTP) : tableau de bord, commandes assignées, historique, règlement des espèces, profil.

### Dépenses et paiements
- **Paiements** (`/payments`) : encaissements reçus (export Excel) ; **Impayés** (`/payments/due`).
- **Dépenses** (`/payments/expenses`), **Dépenses récurrentes**, **Catégories de dépenses**.
**À tester :** ajouter une dépense, la rendre récurrente, exporter les paiements.

### Rapports (`/reports/...`)
16 rapports : articles, catégories, **ventes**, dépenses, résumé des dépenses, impayés, paiements d'impayés reçus, journal d'impression, livraison, espèces à la livraison, **commandes annulées**, **articles supprimés de la cuisine**, **commandes supprimées**, taxes, remboursements, commandes. Filtres de dates, exports.
**À tester :** comparer le rapport de ventes avec les commandes de la liste.

### Réglages (`/settings`)
Une trentaine d'onglets : restaurant, application, **équipes de travail / horaires**, ouverture-fermeture, **établissements**, devise, **e-mail (SMTP)**, **taxes**, **passerelles de paiement**, **thème**, **rôles et droits**, **abonnement et facturation**, **réservations**, à propos, **site client**, écran client, **ticket de caisse**, **imprimantes**, téléchargements (application), images des plats, **livraison** (frais, zones), **allergènes UE**, **KOT**, **annulation**, **commandes** (numérotation, types), **motifs de remboursement**, **notifications**, langue.
**À tester :** ajouter une taxe et voir l'effet sur une commande ; changer le site client (messages, ouverture) ; créer un rôle personnalisé.

### Abonnement (`/billing/upgrade-plan`)
Choix et paiement d'un forfait par le restaurateur, avec les passerelles activées par le super admin.

---

## 3. Site client / menu QR (`/restaurant/{identifiant}`)
- **Accueil et menu** : cartes de menu, catégories, recherche, filtre végétarien, ajout au panier, variantes et modificateurs ; choix du type de commande (sur place / à emporter / livraison) ; choix de l'établissement.
- **Panier et commande** : récapitulatif, taxes, remises, paiement en ligne ou à la caisse, page de confirmation (`orders-success`).
- **Appeler un serveur**, **Réserver une table** (`book-a-table`), **Mes commandes**, **Mes réservations**, **Mes adresses**, **Profil**, **À propos**, **Contact**, sélecteur de langue.
- **Table QR** : `/restaurant/table/{hash}` ouvre le menu déjà lié à la table.
**À tester sur téléphone :** scanner le QR d'une table, commander, suivre la commande, appeler le serveur.

---

## 4. Espace super admin
- **Tableau de bord** : étapes de mise en route (installation, SMTP, tâches planifiées (CRON), nom de l'application…).
- **Restaurants** (`/restaurants`) : création, modification, activation, **connexion en tant que** (impersonation) avec journal (`/impersonation-logs`).
- **Recalcul des commandes** (`/order-money-recalculation`) : outil de correction des montants.
- **Paiements** (`/restaurant-payments`), **Forfaits** (`/packages`), **Facturation** (`/invoices`), **Demandes hors-ligne** (`/offline-plan`).
- **Super admins** (`/users`), **Réglages** (`/superadmin-settings`) : application, passerelles de paiement de la plateforme (Stripe, Razorpay, PayPal, Flutterwave, Paystack, Mollie, PayFast, Xendit, Tap, Epay, Tlync, Paddle), thème, rôles, devises, langues, stockage, e-mail.
- **Mise à jour** (`/app-update`), **Modules personnalisés** (`/custom-modules`), **Site vitrine** (`/landing-sites`) : textes, fonctionnalités, avis, FAQ, contact (l'ancienne page d'accueil les utilisait ; la nouvelle ne les lit pas encore).

---

## 5. Parcours de test de bout en bout (environ 1 heure)
1. **Super admin** : créer un restaurant (ou utiliser « Demo Restaurant »), vérifier son forfait.
2. **Admin** : régler la devise, une taxe, l'imprimante, les horaires.
3. **Menu** : créer une catégorie, un modificateur, un plat avec variante.
4. **Salle** : créer une zone et une table, télécharger son QR.
5. **Client** : ouvrir le QR, commander 2 plats, appeler le serveur.
6. **Cuisine** : voir le bon (KOT), le passer « prêt ».
7. **Caisse** : retrouver la commande, ajouter un pourboire, payer en deux parts, imprimer l'addition.
8. **Réservation** : réserver côté client, assigner une table côté restaurant.
9. **Livraison** : créer une commande livraison, assigner un livreur, suivre jusqu'à « livrée », régler les espèces.
10. **Rapports** : vérifier ventes, taxes, annulations ; **exporter**.

## 6. Écart avec votre brief produit
| Votre brief | Dans Jimbe aujourd'hui |
|---|---|
| Menu QR par table | **Présent** |
| Mode de service assisté / autonome / les deux | **Absent** (à développer) |
| Mobile Money (Wave, Orange Money, MTN) | **Absent**. Passerelles présentes : Stripe, Razorpay, PayPal, Flutterwave, Paystack, Mollie… Flutterwave et Paystack couvrent la Côte d'Ivoire |
| Suivi de statut en temps réel côté client | Statuts présents ; temps réel via Pusher (à configurer) |
| Multi-langue, français d'abord | Interface **anglais seulement** (autres langues listées mais fichiers de traduction absents) |
| Réservation en ligne avec disponibilité | **Présent** (à tester la gestion des créneaux) |
| CRM : base clients, fidélité | Base clients présente ; **fidélité** présente dans la caisse (à confirmer) |
| CRM marketing SMS / e-mails groupés | **Absent** (e-mails de service seulement) |
| Fournisseurs, recettes, coût matière, stocks | **Absents** |
| Personnel : pointage, tâches | Comptes et rôles ; **pointage / tâches absents** |
| KPI avec comparaison N-1 | Comparaison période précédente présente ; **N-1 à vérifier** |
| IA (photo → plat, prévisions, chatbot) | **Absent** |
| SEO local, schema.org, sitemap | **Absent** |
| Isolation stricte des restaurants | Par identifiant de restaurant côté serveur (Laravel), pas par RLS comme prévu avec Supabase |

Ce tableau sert de base pour choisir **l'ordre des fonctionnalités à développer** après vos tests.
