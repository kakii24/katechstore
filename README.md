# ⚡ KA Tech Store — Plateforme E-Commerce Complète, Caisse POS & Marketing IA

> **Site en production** : [katechstore.net](https://katechstore.net)  
> **Contact & Support** : kaa.tech.48@gmail.com | contact@katechstore.net  
> **Dépôt GitHub Privé** : [github.com/kakii24/katechstore](https://github.com/kakii24/katechstore)

---

## 🌟 Présentation Générale du Projet

**KA Tech Store** est une solution tout-en-un développée sur mesure pour l'écosystème commercial tech en Algérie. Elle combine :
1. **Un Storefront E-Commerce Public Ultra-Rapide** avec configurateur PC sur mesure, panier, commande et galerie multi-photos.
2. **Un Panneau d'Administration Centralisé (`/admin`)** pour piloter le catalogue, le stock, les commandes en ligne et les prix d'achat.
3. **Un Système de Caisse POS & Atelier Réparation (`caisse.php`)** réservé au magasin physique, avec calcul en temps réel de la marge brute et du profit net.
4. **Un Système de Marketing par E-mail Automatisé** piloté par **Google Gemini AI** et **Brevo SMTP (Sendinblue)** synchronisant l'ensemble des clients du site.

---

## 🛒 1. Storefront E-Commerce Public (`katechstore.net`)

- **Catalogue Produits Dynamique** : PC portables bureautique/gamer, ordinateurs de bureau, composants, smartphones et accessoires avec recherche instantanée.
- **Galerie Multi-Images Haute Performance** : Carrousel matériel avec miniatures synchronisées et zoom interactif.
- **Configurateur PC Gamer & Pro (`/builder`)** : Montage sur mesure étape par étape (CPU, GPU, RAM, Stockage, Boîtier, Alimentation) avec calcul automatique du total.
- **Tunnel de Commande Sans Friction (`/checkout`)** : Prise de commande rapide adaptée au marché algérien avec livraison 58 wilayas et paiement à la livraison.
- **Espace Compte Client, Avis & Favoris** : Authentification JWT, liste de souhaits et système d'avis vérifiés.
- **Capture Newsletter** : Formulaire d'abonnement dans le pied de page relié directement à la base de données et synchronisé à Brevo.

---

## 🛠️ 2. Panneau d'Administration (`/admin`)

> 🔑 **Identifiants d'Accès Dashboard Développeur / Code Source (Mode Local / Dépôt GitHub)** :  
> - **Nom d'utilisateur** : `admin` (ou `admin@katech.dz`)  
> - **Mot de passe** : `admin`  
> *(Note : Sur le site en production, l'accès administrateur est sécurisé par les identifiants de production enregistrés en base de données).*

- **Gestion des Produits & Stock** :
  - Création et modification de produits avec téléversement de photos multiples.
  - Saisie du **Prix d'Achat (Cost Price)** sécurisé, masqué au public, pour calculer la rentabilité exacte.
  - Définition de prix promotionnels déclenchant automatiquement des campagnes marketing par IA.
- **Gestion des Commandes Web** : Suivi des statuts (en attente, confirmée, expédiée, livrée, annulée).
- **Liste des Abonnés & Clients** : Visualisation des inscriptions newsletter et acheteurs.
- **Raccourcis Directs dans le Header** :
  - 💵 **Accès direct Caisse** (`/caisse.php`)
  - ✨ **Test Marketing IA** (envoi d'un e-mail de démonstration Gemini + Brevo en un clic)

---

## 💻 3. Caisse Boutique & Gestion Magasin (`caisse.php`)

Système POS sécurisé (HTTP 401 & écran de verrouillage administrateur) dédié au comptoir :
- **Ventes Comptoir** : Saisie du modèle, prix de vente, mode de paiement (Espèces, BaridiMob, CCP, Virement) et impression du ticket de garantie client.
- **Atelier Réparations** : Suivi des dépannages techniques et impression du bon de prise en charge.
- **Dépenses Quotidiennes** : Suivi des sorties de caisse pour le calcul du solde réel.
- **Analyse Financière & Marge Nette** : Calcul automatique de la marge brute et du bénéfice net en comparant prix de vente et prix d'achat.
- **Commandes Web en Temps Réel** : Visualisation immédiate des commandes passées sur le site avec option de confirmation ou suppression.
- **Clôture Journalière (Rapport Z)** : Bilan imprimable en fin de journée.

---

## 📧 4. Système d'Email Marketing Automatisé (Brevo + Google Gemini)

Le système de marketing par e-mail fonctionne de manière transverse sur toute la plateforme :

### A. Capture des Abonnés & Brevo Sync
- **Points de collecte** :
  1. Formulaire Newsletter dans le footer de la boutique.
  2. Finalisation de commande (Checkout) : l'e-mail de l'acheteur est automatiquement abonné.
  3. Création de compte utilisateur (`/register`).
- **Synchronisation automatique** vers la **Liste Brevo #2** via l'API REST `POST https://api.brevo.com/v3/contacts`.
- **Désabonnement en 1 clic** : Route sécurisée `/unsubscribe?token=...` retirant le contact de la base et de la liste Brevo.

### B. Générateur d'E-mails par Intelligence Artificielle (Gemini 2.5 Flash)
- Prompt ingénierie sur mesure produisant des e-mails HTML responsive modernes avec palette KA Tech (bleu tech, dark mode, call-to-action percutants).
- Supporte deux types de campagnes :
  - `new_item` : Annonce exclusive de nouvel arrivage avec visuel, fiche technique et bouton "Découvrir le produit".
  - `discount` : Alerte baisse de prix / promotion avec badge de réduction en pourcentage (ex: `-15%`).

### C. Déclencheurs Automatiques (Triggers Boutique)
- **Ajout d'un nouveau produit** dans le catalogue admin (`POST /api/v1/products`) ➔ Génération Gemini et diffusion instantanée par e-mail à tous les abonnés.
- **Application d'une remise de prix** (`PUT /api/v1/products/:id`) ➔ Campagne flash promotionnelle envoyée automatiquement.
- **Bouton de Test Immédiat** : Endpoint `/api/test-marketing-email` disponible dans la barre admin et la caisse.

### D. E-mail de Bienvenue Automatique aux Nouveaux Membres
- Dès qu'un nouvel utilisateur s'inscrit sur le site (`POST /api/v1/auth/register`), la passerelle API déclenche de manière asynchrone la fonction `sendWelcomeEmail(userEmail, userName)`.
- Envoi d'un e-mail transactionnel de bienvenue élégant via Brevo SMTP (`news@katechstore.net`) présentant les avantages membres : suivi des commandes 58 wilayas, arrivages PC et configs gamer, garantie officielle et bouton d'action vers la boutique.
- Synchronisation simultanée du contact vers la liste Brevo #2 et la table `subscribers`.
- Route de test immédiat : `/api/test-welcome-email?email=...`.

---

## 🔐 5. Système Complet de Réinitialisation de Mot de Passe

Gestion de bout en bout de la sécurité des comptes clients et administrateurs :
1. **Base de Données** :
   - Table `password_resets` (`id`, `user_id`, `email`, `token_hash`, `expires_at`, `used_at`, `created_at`).
   - Colonnes `reset_password_token` et `reset_password_expires` intégrées dans la table `users`.
2. **Page "Mot de passe oublié" (`/forgot-password`)** :
   - Interface intuitive accessible depuis l'écran de connexion (`/auth`).
   - Saisie de l'e-mail avec protection anti-énumération (message générique systématique).
   - Génération d'un jeton cryptographique aléatoire de 32 octets (hexadécimal), hachage SHA-256 et expiration fixée à 1 heure.
   - Envoi d'un e-mail transactionnel de réinitialisation via Brevo contenant un lien unique : `https://katechstore.net/reset-password?token=<token>`.
3. **Page "Nouveau mot de passe" (`/reset-password`)** :
   - Lecture automatique du paramètre `?token=` depuis l'URL avec alertes si jeton manquant ou invalide.
   - Formulaire sécurisé avec confirmation du mot de passe (vérification de la longueur minimale et conformité).
   - Hachage cryptographique sécurisé du nouveau mot de passe, invalidation du jeton utilisé (`used_at`) et mise à jour immédiate du compte.
   - Envoi automatique d'un e-mail d'alerte de sécurité via Brevo confirmant la mise à jour et avertissant le client en cas d'activité suspecte.

---

## 🔒 Variables d'Environnement & Sécurité

Les secrets de production sont stockés dans le fichier `.env` protégé (non versionné dans git, bloqué par `.htaccess`) :
```env
BREVO_API_KEY=votre_cle_brevo_securisee
BREVO_LIST_ID=2
BREVO_SENDER_EMAIL=news@katechstore.net
BREVO_SENDER_NAME="KA Tech Store"
BREVO_REPLY_TO=kaa.tech.48@gmail.com
GEMINI_API_KEY=AQ.Ab8RN6LpdUxetV_E3D10YZQaHSktMkIXBg07mDgZKQ_mjrsVQQ
```

---

## 🚀 Lanceurs & Scripts Utilitaires

- **`Lancer-Site-Vite.bat`** : Lance le serveur de développement local du site web.
- **`Lancer-Caisse.bat`** : Ouvre directement la caisse de la boutique en mode plein écran.
- **`Push-GitHub.bat`** : Automatise la synchronisation Git avec le dépôt GitHub privé.
