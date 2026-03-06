# Modele Entite-Association - Omnes MarketPlace

## Diagramme Mermaid

Coller UNIQUEMENT le contenu entre les balises ```mermaid sur https://mermaid.live

```mermaid
erDiagram
    UTILISATEURS {
        INT id PK
        VARCHAR prenom
        VARCHAR nom
        VARCHAR email UK
        VARCHAR mot_de_passe
        VARCHAR telephone
        TEXT adresse
        ENUM role
        TINYINT actif
        DATETIME date_creation
    }

    ARTICLES {
        INT id PK
        INT vendeur_id FK
        VARCHAR titre
        TEXT description
        DECIMAL prix
        ENUM categorie
        ENUM type_vente
        ENUM gamme
        VARCHAR image_url
        ENUM statut
        DATETIME date_debut_enchere
        DATETIME date_fin_enchere
        DATETIME date_creation
    }

    PANIER {
        INT id PK
        INT utilisateur_id FK
        INT article_id FK
        INT quantite
        DECIMAL prix_negocie
        INT negociation_id FK
        INT enchere_id FK
        DATETIME date_ajout
    }

    COMMANDES {
        INT id PK
        INT acheteur_id FK
        DECIMAL total
        TEXT adresse_livraison
        ENUM statut
        DATETIME date_creation
    }

    COMMANDE_ARTICLES {
        INT id PK
        INT commande_id FK
        INT article_id FK
        DECIMAL prix
    }

    ENCHERES {
        INT id PK
        INT article_id FK
        INT acheteur_id FK
        DECIMAL montant_max
        DECIMAL prix_paye
        ENUM statut
        DATETIME date_creation
    }

    NEGOCIATIONS {
        INT id PK
        INT article_id FK
        INT acheteur_id FK
        INT vendeur_id FK
        ENUM statut
        DECIMAL prix_accorde
        DATETIME date_resolution
        DATETIME date_creation
    }

    NEGOCIATION_MESSAGES {
        INT id PK
        INT negociation_id FK
        INT auteur_id FK
        DECIMAL montant_propose
        TEXT message
        ENUM statut
        DATETIME date_creation
    }

    AVIS {
        INT id PK
        INT article_id FK
        INT auteur_id FK
        TINYINT note
        TEXT commentaire
        DATETIME date_creation
    }

    NOTIFICATIONS {
        INT id PK
        INT utilisateur_id FK
        TEXT message
        TINYINT lue
        DATETIME date_creation
    }

    ALERTES_RECHERCHE {
        INT id PK
        INT utilisateur_id FK
        VARCHAR mot_cle
        ENUM categorie
        DECIMAL prix_max
        DATETIME date_creation
    }

    CARTES_BANCAIRES {
        INT id PK
        VARCHAR numero_carte
        VARCHAR expiration
        VARCHAR cvv
        VARCHAR titulaire
    }

    CARTES_REDUCTION {
        INT id PK
        INT utilisateur_id FK
        INT pourcentage
        INT commande_id FK
        TINYINT utilisee
        DATETIME date_creation
    }

    UTILISATEURS ||--o{ ARTICLES : "vend"
    UTILISATEURS ||--o{ PANIER : "possede"
    UTILISATEURS ||--o{ COMMANDES : "passe"
    UTILISATEURS ||--o{ ENCHERES : "encherit"
    UTILISATEURS ||--o{ NEGOCIATIONS : "negocie-acheteur"
    UTILISATEURS ||--o{ NEGOCIATION_MESSAGES : "ecrit"
    UTILISATEURS ||--o{ AVIS : "redige"
    UTILISATEURS ||--o{ NOTIFICATIONS : "recoit"
    UTILISATEURS ||--o{ ALERTES_RECHERCHE : "cree"
    UTILISATEURS ||--o{ CARTES_REDUCTION : "obtient"
    ARTICLES ||--o{ PANIER : "est-dans"
    ARTICLES ||--o{ COMMANDE_ARTICLES : "fait-partie-de"
    ARTICLES ||--o{ ENCHERES : "recoit-enchere"
    ARTICLES ||--o{ NEGOCIATIONS : "est-negocie"
    ARTICLES ||--o{ AVIS : "recoit-avis"
    COMMANDES ||--o{ COMMANDE_ARTICLES : "contient"
    COMMANDES ||--o{ CARTES_REDUCTION : "genere"
    NEGOCIATIONS ||--o{ NEGOCIATION_MESSAGES : "contient"
    NEGOCIATIONS ||--o{ PANIER : "lie-panier"
    ENCHERES ||--o{ PANIER : "lie-panier"
```

## Legende des ENUM

- **UTILISATEURS.role** : acheteur, vendeur, administrateur
- **ARTICLES.categorie** : Electronique, Vetements, Maison, Livres, Sports, Divers
- **ARTICLES.type_vente** : achat_immediat, negociation, meilleure_offre
- **ARTICLES.gamme** : regulier, haut_de_gamme, rare
- **ARTICLES.statut** : disponible, vendu, retire
- **ENCHERES.statut** : en_attente, gagnant, perdant
- **COMMANDES.statut** : en_attente, confirmee, expediee, livree, annulee
- **NEGOCIATIONS.statut** : en_cours, accepte, refuse, expire
- **NEGOCIATION_MESSAGES.statut** : en_attente, accepte, refuse
- **AVIS.note** : CHECK 1 a 5

## Contraintes notables

- **PANIER** : UNIQUE (utilisateur_id, article_id)
- **ENCHERES** : UNIQUE (article_id, acheteur_id)
- **AVIS** : UNIQUE (article_id, auteur_id)
- **PANIER.negociation_id** : FK vers NEGOCIATIONS, ON DELETE SET NULL
- **PANIER.enchere_id** : FK vers ENCHERES, ON DELETE SET NULL
- Toutes les FK principales : ON DELETE CASCADE
