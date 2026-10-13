# Feuille de route — Menu Digital (base Jimeb / Laravel + Livewire)

Positionnement : restaurants haut de gamme en Côte d'Ivoire. Le service humain reste central ; le digital est un renfort.
Principe : une phase à la fois, chacune livrable et testable. On ne commence pas la suivante tant que la précédente n'est pas validée.

Légende : ⬜ à faire · 🟨 en cours · ✅ fait

---

## Règles de travail

- Une branche par phase (`phase-1-fondation`, `phase-2-menu-public`, …), fusionnée une fois validée.
- Chaque phase a une liste de contrôle « Terminé quand… » ; on la coche avant de passer à la suivante.
- Aucune modification dans `vendor/` ni dans les fichiers du cœur sans nécessité : préférer ajouter des vues, composants et traductions pour faciliter les futures mises à jour.
- On ne supprime pas les modules inutiles : on les **masque** (forfaits / permissions / menu latéral). Plus sûr et réversible.
- Toute nouvelle fonctionnalité = une migration, un test minimal, une ligne dans ce fichier.

---

## Phase 0 — Cadrage et environnement ⬜

- [ ] Décider du nom de la marque (Jimeb conservé ou nouveau nom)
- [ ] Charte : logo, 2 couleurs principales, polices (défaut proposé : sombre + or, titres à empattements)
- [ ] Vérifier la licence CodeCanyon (Regular = un seul produit final ; revente SaaS à des tiers = Extended)
- [ ] Application locale accessible : `http://resto-saas.test` ✔ (tourne sur votre machine)
- [ ] Données de démonstration : 1 restaurant, 4 catégories, ~15 plats avec photos, 5 tables

**Terminé quand** : on a une charte écrite et un restaurant de démo complet pour tester chaque écran.

## Phase 1 — Fondation (marque, langue, nettoyage) ⬜

- [ ] Traduction française complète de l'interface (`lang/fr`), français par défaut
- [ ] Devise XOF (FCFA) par défaut, fuseau Africa/Abidjan, format de date local
- [ ] Remplacement du nom, logo, favicon, couleurs (config Tailwind + variables CSS)
- [ ] Menu latéral simplifié : ne garder que Menu, Tables/QR, Commandes, Réglages ; masquer POS, KDS, inventaire, kiosque, livraison, etc.
- [ ] Onboarding restaurateur raccourci (création du resto → premier plat → premier QR)

**Terminé quand** : un restaurateur français se connecte et ne voit aucun mot anglais ni module hors MVP.

## Phase 2 — Menu public QR (cœur du produit) ⬜

- [ ] Refonte du design de la page scannée (mobile d'abord, ambiance haut de gamme)
- [ ] Fiche plat soignée : grande photo, description, allergènes et régimes en icônes
- [ ] **Mode de service réglable** : consultation seule / commande autonome (réglage par restaurant, panier et paiement masqués en consultation)
- [ ] **Accords** : champ « se marie avec » sur un plat, lié à un autre article (vin, boisson) ; affichage sur la fiche
- [ ] Rupture de stock en un clic depuis le téléphone du restaurateur
- [ ] Génération et impression des QR codes par table (planche A4 prête à imprimer)

**Terminé quand** : on scanne un QR sur un vrai téléphone, on voit le menu, on bascule le mode de service et le comportement change.

## Phase 3 — Mobile Money ⬜

- [ ] Choisir l'agrégateur (CinetPay, PayDunya ou Flutterwave) et ouvrir un compte test
- [ ] Intégrer Wave, Orange Money, MTN MoMo, actifs seulement en mode « commande autonome »
- [ ] Webhooks de confirmation + statut de commande mis à jour
- [ ] Tests en mode sandbox, puis un vrai paiement de faible montant

**Terminé quand** : un client commande et paie en Mobile Money, et le restaurateur voit la commande payée en temps réel.

## Phase 4 — Back-office restaurateur ⬜

- [ ] Refonte du design du tableau de bord (liste des commandes du jour, en attente / en cours / terminées)
- [ ] Édition simple des plats, prix, photos, catégories (écrans épurés)
- [ ] KPI essentiels : chiffre d'affaires jour / semaine / mois, plats les plus vendus
- [ ] Export CSV des clients

**Terminé quand** : un restaurateur peu technophile gère son menu et suit sa journée sans aide.

## Phase 5 — Test terrain ⬜

- [ ] Hébergement de production (domaine, HTTPS, sauvegardes, files d'attente, e-mails)
- [ ] 1 à 2 restaurants pilotes, formation de 30 min
- [ ] Recueil des retours sur 2 semaines, correction des points bloquants

**Terminé quand** : un restaurant pilote utilise le produit chaque jour sans notre intervention.

---

## Phase 6 et plus — après validation du MVP

Dans cet ordre de priorité :

1. Site vitrine du restaurant (refonte du design, SEO local, galerie, réservation)
2. CRM : campagnes SMS / e-mail, programme de fidélité, avis clients
3. Gestion : fournisseurs, coût matière, alertes de stock, pointage du personnel
4. Multi-langue (anglais) si la clientèle internationale se confirme
5. **Une seule IA** : prévision des achats (« combien de poulets acheter demain »), qui s'appuie sur l'historique des commandes
6. Plus tard : assistant IA du site, rédaction automatique des SMS / posts, analyse des avis

## Hors périmètre pour l'instant

Génération de menu par photo, menu prédictif météo, commande vocale, chatbot manager, comparaison avec d'autres restaurants.

---

## Journal des décisions

| Date | Décision |
|---|---|
| 2026-10-13 | On garde Laravel/Livewire (base Jimeb) plutôt que de repartir en Next.js + Supabase |
| 2026-10-13 | Cible : restaurants haut de gamme ; une seule brique (Menu Digital) d'abord |
