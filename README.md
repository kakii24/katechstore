# KA Tech — Système de Caisse & Gestion des Paiements (Sécurisé & Réservé aux Administrateurs)

Application sur mesure conçue pour la boutique **KA Tech** afin de suivre en temps réel toutes les entrées et sorties d'argent, ventes de PC, réparations, dépenses du magasin et notes d'équipe.

---

## 🔒 Sécurité & Confidentialité Strictes (Non Public)

- **Accès Réservé Exclusivement aux Administrateurs** :  
  La page ne s'ouvre pas au public. Dès son ouverture, un **écran de verrouillage sécurisé** demande l'authentification avec les identifiants administrateur (`admin@katech.a`).
- **Données Protégées (HTTP 401 Unauthorized pour le public)** :  
  Les données financières ne sont pas accessibles aux visiteurs de votre site web. Toute tentative de lecture sans session administrateur est automatiquement bloquée par le serveur Hostinger.
- **Bouton de Verrouillage Immédiat** :  
  Un bouton rouge **« 🔒 Verrouiller »** en haut à droite permet de reverrouiller immédiatement la caisse lorsque l'employé quitte le poste.

---

## 🚀 Utilisation sur le PC du Magasin

Un raccourci est prêt sur votre Bureau Windows :  
👉 **`KA Tech - Caisse & Ventes`**

1. **Double-cliquez sur le raccourci sur le Bureau** (ou lancez `Lancer-Caisse.bat`).
2. Saisissez votre mot de passe administrateur pour déverrouiller la caisse.
3. Le statut affiche **🟢 "Synchronisé en direct"** : toutes les opérations saisies sont enregistrées immédiatement dans la base de données sécurisée de votre serveur Hostinger.

---

## 🛠️ Fonctionnalités du Système

| Type d'opération | Détails saisis par l'employé | Actions & Reçus |
| :--- | :--- | :--- |
| **💻 Vente PC / Matériel** | Modèle PC/Laptop, Prix en DA, Nom & Tél client (avec lien WhatsApp), Mode de règlement (Espèces, BaridiMob, Virement, CCP), Garantie offerte (3 mois, 6 mois, 1 an), Vendeur, Notes. | Impression du **Ticket / Facture de vente client** |
| **🔧 Réparation PC** | Modèle de la machine, Diagnostic & Panne résolue, Montant encaissé (DA), Nom & Tél client, Statut (*Réparé & Payé, Prêt pour retrait, En cours*), Technicien. | Impression du **Bon de réparation client** |
| **💸 Dépense Magasin** | Motif (consommables, pâte thermique, loyer, internet, café, transport...), Montant déboursé (DA), Justificatif/notes. | Déduit automatiquement du solde espèces |
| **📝 Notes & Remarques** | Consignes d'équipe, rappels pour le lendemain, clients en attente de pièces. | Visible par l'administrateur et l'équipe |

---

## 📊 Indicateurs Financiers en Temps Réel
1. **Solde Espèces Caisse (Net Cash)** : Total espèces reçues moins dépenses en espèces.
2. **Total Ventes PC** : Chiffre d'affaires et compteur du nombre d'ordinateurs vendus.
3. **Total Réparations** : Montant encaissé pour la maintenance technique.
4. **Total Dépenses** : Suivi des sorties d'argent de la journée.
5. **BaridiMob & Virements** : Suivi séparé des encaissements digitaux.

---

## 🖨️ Clôture Journalière (Rapport Z) & Export
- **Bouton « Clôture Z »** : Génère en un clic le rapport récapitulatif de fin de journée prêt à être imprimé et signé.
- **Exportation Excel / CSV** : Téléchargement instantané de toutes les opérations pour votre comptabilité.

---

## 🌐 Déploiement Cloud sur Hostinger (Optionnel)
Pour y accéder depuis votre smartphone en déplacement :
1. Ouvrez **Hostinger hPanel** > **File Manager** (Gestionnaire de fichiers).
2. Ouvrez le dossier `public_html/`.
3. Uploadez **`caisse.php`**.
4. Accessible à l'adresse : `https://bisque-shrew-404215.hostingersite.com/caisse.php`  
   *(Même sur le web, la page exige obligatoirement le mot de passe administrateur pour s'ouvrir).*
