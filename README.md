# Omnes Marketplace

![Omnes Marketplace](Marketplace/images/OMNES.png)

## 1. Présentation
Omnes Marketplace est une plateforme web de type marketplace, conçue pour la communauté Omnes Education.  
Elle permet aux utilisateurs d’acheter et de vendre des produits (électronique, vêtements, maison, livres, sports, etc.) dans une interface simple et structurée.

### Fonctionnalités principales
- Gestion de comptes avec rôles (administrateur, vendeur, acheteur)
- Publication et consultation d’annonces produits
- Recherche et navigation par catégories
- Parcours d’achat avec panier
- Gestion des interactions acheteur-vendeur (achat direct et négociation)
- Espace d’administration pour superviser les comptes et contenus

## 2. Installation rapide (Docker Compose)
### Prérequis
- Docker
- Docker Compose

### Lancement
Depuis la racine du projet :

```bash
docker compose up --build -d
```

### Accès
- Application : [http://localhost:8080](http://localhost:8080)
- phpMyAdmin : [http://localhost:8081](http://localhost:8081)

Identifiants phpMyAdmin par défaut :
- Utilisateur : `root`
- Mot de passe : `root_password`

### Arrêt
```bash
docker compose down
```

### Réinitialisation complète (si nécessaire)
```bash
docker compose down -v
docker compose up --build -d
```

## Projet réalisé par
- Regnier Come
- Soeiro-Vuong David
- Hippolyte Durand

Étudiant en ING3 à l'ECE Omnes Education  
Février / Mars 2026
