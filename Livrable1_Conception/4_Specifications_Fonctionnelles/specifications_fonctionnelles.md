# Specifications Fonctionnelles - Omnes MarketPlace

## 1. Fonctionnalites principales

### F1 - Gestion des utilisateurs
- Inscription acheteur (auto-inscription)
- Connexion / Deconnexion par email + mot de passe
- 3 roles : Administrateur, Vendeur, Acheteur
- Profil utilisateur avec nom, prenom, email, telephone, adresse
- Mot de passe hashe (bcrypt)

### F2 - Gestion des articles
- Creation d'article par le vendeur : titre, description (qualite/defaut), prix, categorie, gamme, type de vente, image(s), video optionnelle
- Modification / Suppression par le vendeur proprietaire ou l'admin
- 6 categories : Electronique, Vetements, Maison, Livres, Sports, Divers
- 3 gammes : Articles rares, Articles hauts de gamme, Articles reguliers
- 3 types de vente : Achat immediat, Negociation, Meilleure offre
- Numero d'identification unique par article

### F3 - Achat immediat
- L'acheteur ajoute l'article au panier
- Passe a la commande via le formulaire de paiement
- L'article passe en statut "vendu" apres paiement

### F4 - Negociation (Transaction vendeur-client)
- L'acheteur propose un prix au vendeur
- Le vendeur accepte, refuse, ou fait une contre-offre
- Maximum 5 rounds de negociation
- Clause legale : l'acheteur est engage si le vendeur accepte
- Article ajoute automatiquement au panier au prix accorde

### F5 - Meilleure offre (Encheres scellees)
- L'acheteur soumet son prix maximum
- Periode definie (date debut / date fin)
- Le systeme encherit automatiquement : prix du 2e plus offrant + 1 EUR
- L'admin resout l'enchere a la cloture
- Le gagnant paie le prix auto-calcule (pas son maximum)
- Article ajoute au panier du gagnant

### F6 - Panier
- Affichage des articles avec image, titre, prix
- Sous-total calcule
- Suppression possible pour les articles achat immediat
- Articles negocies et remportes aux encheres non supprimables (ou avec avertissement)

### F7 - Paiement
- Formulaire de livraison : nom, prenom, adresse (lignes 1-2), ville, code postal, pays, telephone
- Formulaire de paiement : type de carte (Visa, MasterCard, Amex, PayPal), numero, nom sur la carte, expiration, CVV
- Validation des donnees de carte en base de donnees
- Carte de reduction generee si total > 100 EUR (10%) ou > 200 EUR (20%)

### F8 - Notifications
- Notifications en base de donnees (confirmation commande, negociation, encheres)
- Systeme d'alertes de recherche : l'acheteur definit des criteres (mot-cle, categorie, prix max)
- Notification automatique quand un article correspondant est publie

### F9 - Confirmation d'achat
- Email de confirmation envoye a l'acheteur apres paiement (via SMTP / MailHog)
- Contient : numero de commande, liste des articles, total, date
- Notification en base de donnees

### F10 - Avis et evaluations
- Note de 1 a 5 etoiles par article
- Commentaire textuel
- Un seul avis par utilisateur par article

---

## 2. Roles des utilisateurs

### Administrateur
- Super-vendeur : peut publier ses propres articles
- Cree et supprime des comptes vendeurs (email, pseudo, nom)
- Gere tous les articles (suppression)
- Resout les encheres a leur cloture
- Accede au tableau de bord avec statistiques globales

### Vendeur
- Se connecte avec son pseudo et email
- Affiche son mur de profil (nom, photo, image de fond)
- Publie des articles avec toutes les informations requises
- Gere ses articles (modification, suppression)
- Repond aux negociations (accepter, refuser, contre-offre)
- Consulte ses statistiques (articles en ligne, vendus, chiffre d'affaires)

### Acheteur
- S'inscrit librement sur le site
- Consulte et recherche des articles
- Achete en immediat, negocie, ou encherit
- Gere son panier
- Passe commande avec paiement
- Depose des avis et notes
- Cree des alertes de recherche
- Consulte son historique de commandes

---

## 3. Cas d'utilisation (Use Cases)

### UC1 - Inscription acheteur
- **Acteur** : Visiteur
- **Pre-condition** : Aucune
- **Scenario** : Le visiteur remplit le formulaire (prenom, nom, email, mot de passe, telephone). Le compte est cree avec le role "acheteur".
- **Post-condition** : Le visiteur est connecte et redirige vers l'accueil.

### UC2 - Connexion utilisateur
- **Acteur** : Tout utilisateur
- **Pre-condition** : Compte existant et actif
- **Scenario** : L'utilisateur saisit son email et mot de passe. Le systeme verifie les identifiants en BDD (password_verify). Redirection selon le role.
- **Post-condition** : Session PHP active avec user_id et user_role.

### UC3 - Achat immediat
- **Acteur** : Acheteur connecte
- **Pre-condition** : Article disponible en achat immediat
- **Scenario** : L'acheteur consulte l'article, clique "Ajouter au panier", va au panier, clique "Passer au paiement", remplit le formulaire, valide. L'article passe en "vendu".
- **Post-condition** : Commande creee, notification envoyee, email de confirmation.

### UC4 - Negociation
- **Acteur** : Acheteur + Vendeur
- **Pre-condition** : Article disponible en negociation
- **Scenario** : L'acheteur propose un prix. Le vendeur accepte, refuse ou contre-propose. Maximum 5 allers-retours. Si accord, l'article est ajoute au panier de l'acheteur au prix accorde.
- **Post-condition** : Article marque "vendu", negociation "accepte", panier mis a jour.

### UC5 - Meilleure offre
- **Acteur** : Acheteur + Admin
- **Pre-condition** : Article disponible en meilleure offre, periode d'encheres ouverte
- **Scenario** : L'acheteur definit son prix maximum. A la cloture, l'admin resout : le gagnant paie le 2e prix + 1 EUR (plafonne a son max). L'article est ajoute au panier du gagnant.
- **Post-condition** : Article marque "vendu", notifications a tous les participants.

### UC6 - Administration vendeurs
- **Acteur** : Administrateur
- **Pre-condition** : Connecte en tant qu'admin
- **Scenario** : L'admin accede a la gestion des vendeurs, peut ajouter un vendeur (email, pseudo, prenom, nom, mot de passe) ou le supprimer.
- **Post-condition** : Compte vendeur cree ou supprime en BDD.

### UC7 - Alertes de recherche
- **Acteur** : Acheteur connecte
- **Pre-condition** : Aucune
- **Scenario** : L'acheteur definit des criteres de recherche (mot-cle, categorie, prix max). Quand un article correspondant est publie, une notification est envoyee.
- **Post-condition** : Alerte sauvegardee, notifications futures automatiques.

---

## 4. Interfaces attendues

| Page | Description |
|------|-------------|
| Accueil | Hero carousel, barre de recherche, selection du jour / ventes flash, compteurs animes, categories, comment ca marche, temoignages, coordonnees + Google Maps |
| Tout Parcourir | Catalogue avec filtres (categorie, type de vente, gamme, recherche, tri), articles organises par gamme, pagination |
| Article | Detail avec ID, titre, photo(s), video, description (qualite/defaut), categorie, gamme, prix, vendeur, action selon type de vente, avis |
| Negociation | Interface chat avec historique des offres, compteur de rounds, clause legale, boutons accepter/refuser/contre-offre |
| Panier | Liste des articles avec image, titre, prix, sous-total, boutons de retrait, lien vers paiement |
| Paiement | Formulaire livraison complet + formulaire carte bancaire + validation |
| Notifications | Liste des notifications lues/non-lues + creation/gestion d'alertes de recherche |
| Votre Compte | Profil (nom, adresse, email, paiement masque), clause legale, historique commandes |
| Dashboard Admin | Stats globales, gestion vendeurs (ajout/suppression), gestion articles, gestion encheres |
| Dashboard Vendeur | Mur de profil (photo, fond), stats, liste articles, ajout/edition articles, gestion negociations |

---

## 5. Contraintes techniques

- **Front-end** : HTML5, CSS3, Bootstrap 5.3, JavaScript, jQuery 3.7, Google Fonts (Poppins)
- **Back-end** : PHP 8.x natif (pas de framework)
- **Base de donnees** : MySQL 8+ avec PDO, encodage utf8mb4
- **Serveur de dev** : php -S localhost:8080 ou Docker (Apache + MySQL + MailHog)
- **CDN** : Bootstrap, jQuery, Font Awesome 6.5, Bootstrap Icons 1.11
- **Interdit** : WordPress, CMS, plateformes ready-made
- **Versioning** : Git + GitHub
- **Emails** : SMTP local via MailHog (simulation)
- **Securite** : Mots de passe hashes (bcrypt), requetes preparees (PDO), sessions PHP

---

## 6. Criteres de validation

| Critere | Validation |
|---------|-----------|
| 3 roles utilisateurs | Admin, vendeur, acheteur fonctionnels avec permissions distinctes |
| 3 types de vente | Achat immediat, negociation (max 5 rounds), meilleure offre (encheres scellees) |
| 3 gammes d'articles | Rare, haut de gamme, regulier, avec affichage differencie |
| CRUD articles | Creation, lecture, modification, suppression par vendeur/admin |
| Panier fonctionnel | Ajout, affichage, suppression, passage au paiement |
| Paiement simule | Validation carte en BDD, creation commande, marquage articles vendus |
| Notifications | Envoi en BDD + email de confirmation |
| Alertes de recherche | Creation + declenchement automatique |
| Avis et notes | Systeme 1-5 etoiles avec commentaire |
| Responsive | Adaptation mobile/tablette via Bootstrap |
| Google Maps | Localisation du campus affichee |
| Carte de reduction | Generee pour achat > 100 EUR, applicable sur commande suivante |
