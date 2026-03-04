# Architecture du Systeme - Omnes MarketPlace

## Diagramme Mermaid (coller sur https://mermaid.live pour generer l'image)

```mermaid
graph TB
    subgraph CLIENT["CLIENT (Navigateur Web)"]
        direction TB
        HTML["HTML5 / CSS3<br/>Bootstrap 5.3<br/>Google Fonts Poppins"]
        JS["JavaScript / jQuery 3.7<br/>AJAX<br/>Bootstrap Icons / Font Awesome 6.5"]
        HTML --- JS
    end

    subgraph FRONTEND["FRONT-END (Pages PHP)"]
        direction TB

        subgraph SHARED["Composants partages"]
            HEADER["header.php<br/>Meta, CDN libs, favicon"]
            NAVBAR["navbar.php<br/>Navigation, recherche"]
            FOOTER["footer.php<br/>Liens, newsletter, Google Maps"]
        end

        subgraph PAGES_PUB["Pages publiques"]
            INDEX["index.php<br/>Accueil, hero, categories"]
            PARCOURIR["tout_parcourir.php<br/>Catalogue, filtres"]
            ARTICLE["article.php<br/>Detail article, avis"]
            CONNEXION["connexion.php"]
            INSCRIPTION["inscription.php"]
            PANIER["panier.php<br/>Gestion panier"]
            PAIEMENT["paiement.php<br/>Formulaire paiement"]
            NEGOCIATION["negociation.php<br/>Chat negociation"]
            COMPTE["compte.php<br/>Profil utilisateur"]
            NOTIF["notifications.php<br/>Alertes et recherche"]
            AVIS_PAGE["avis.php<br/>Notes et commentaires"]
            ACCES_NOTIF["acces_notifications.php<br/>Controle acces"]
            ACCES_PANIER["acces_panier.php<br/>Controle acces"]
        end

        subgraph PAGES_ADMIN["Pages Admin"]
            ADMIN_DASH["admin/dashboard.php<br/>Statistiques"]
            ADMIN_ART["admin/gestion_articles.php"]
            ADMIN_VEND["admin/gestion_vendeurs.php"]
            ADMIN_ENCH["admin/gestion_encheres.php<br/>Gestion encheres"]
            ADMIN_DETAIL_ENCH["admin/detail_enchere.php<br/>Detail enchere"]
        end

        subgraph PAGES_VENDEUR["Pages Vendeur"]
            VEND_DASH["vendeur/dashboard.php<br/>Statistiques"]
            VEND_ART["vendeur/mes_articles.php"]
            VEND_ADD["vendeur/ajouter_article.php"]
            VEND_EDIT["vendeur/editer_article.php"]
            VEND_NEGO["vendeur/negociations.php<br/>Gestion negociations"]
        end
    end

    subgraph BACKEND["BACK-END (Scripts PHP)"]
        direction TB
        CONFIG["config/database.php<br/>Connexion PDO"]
        AUTH["php/auth.php<br/>Login, register, logout"]
        ART_ACT["php/article_actions.php<br/>CRUD articles, alertes"]
        PAN_ACT["php/panier_actions.php<br/>Ajout, suppression panier"]
        PAY_ACT["php/paiement_actions.php<br/>Validation paiement, carte reduction"]
        ENCH_ACT["php/enchere_actions.php<br/>Meilleure offre, auto-bid"]
        NEGO_ACT["php/negociation_actions.php<br/>Offres, reponses, max 5 rounds"]
        AVIS_ACT["php/avis_actions.php<br/>Notes 1-5 etoiles"]
        ADM_ACT["php/admin_actions.php<br/>Gestion vendeurs, articles"]
    end

    subgraph DATABASE["BASE DE DONNEES"]
        direction TB
        MYSQL[("MySQL 8+<br/>omnes_marketplace<br/>13 tables<br/>InnoDB / utf8mb4")]
    end

    subgraph ASSETS["RESSOURCES STATIQUES"]
        direction LR
        CSS["css/style.css<br/>Animations, responsive"]
        SCRIPT["js/script.js<br/>AJAX, validation, UI"]
        IMAGES["images/<br/>Photos articles"]
    end

    CLIENT -->|"HTTP GET/POST"| FRONTEND
    FRONTEND -->|"include"| SHARED
    FRONTEND -->|"POST formulaires<br/>AJAX requests"| BACKEND
    BACKEND -->|"PDO queries"| DATABASE
    BACKEND -->|"$_SESSION"| FRONTEND
    CLIENT -->|"CDN"| CDN["Bootstrap 5.3<br/>jQuery 3.7<br/>Font Awesome 6.5<br/>Bootstrap Icons 1.11<br/>Google Fonts"]
    CLIENT -->|"charge"| ASSETS

    style CLIENT fill:#e3f2fd,stroke:#1565c0,stroke-width:2px
    style FRONTEND fill:#fff3e0,stroke:#e65100,stroke-width:2px
    style BACKEND fill:#e8f5e9,stroke:#2e7d32,stroke-width:2px
    style DATABASE fill:#fce4ec,stroke:#c62828,stroke-width:2px
    style ASSETS fill:#f3e5f5,stroke:#6a1b9a,stroke-width:2px
    style CDN fill:#e0f7fa,stroke:#00695c,stroke-width:2px
```

## Diagramme simplifie (version 1 page)

```mermaid
graph LR
    subgraph NAVIGATEUR["Navigateur Web"]
        A["HTML5 / CSS3 / Bootstrap 5.3<br/>JavaScript / jQuery 3.7<br/>AJAX"]
    end

    subgraph SERVEUR["Serveur PHP 8.x"]
        B["Pages PHP<br/>22 pages<br/>+ 3 includes"]
        C["Scripts PHP<br/>8 fichiers actions<br/>Logique metier"]
        B -->|"require / include"| C
    end

    subgraph BDD["MySQL 8+"]
        D[("omnes_marketplace<br/>13 tables<br/>InnoDB")]
    end

    A -->|"HTTP<br/>GET / POST / AJAX"| B
    B -->|"Reponse HTML<br/>+ JSON AJAX"| A
    C -->|"PDO<br/>Requetes SQL"| D
    D -->|"Resultats"| C

    style NAVIGATEUR fill:#e3f2fd,stroke:#1565c0,stroke-width:3px
    style SERVEUR fill:#e8f5e9,stroke:#2e7d32,stroke-width:3px
    style BDD fill:#fce4ec,stroke:#c62828,stroke-width:3px
```

## Flux d'authentification

```mermaid
sequenceDiagram
    participant U as Utilisateur
    participant P as Page PHP
    participant A as auth.php
    participant DB as MySQL

    U->>P: Accede a connexion.php
    P->>U: Formulaire login
    U->>A: POST email + mot_de_passe
    A->>DB: SELECT * FROM utilisateurs WHERE email = ?
    DB->>A: Donnees utilisateur
    A->>A: password_verify()
    alt Succes
        A->>A: $_SESSION[user_id, user_role]
        A->>U: Redirect selon role (admin/vendeur/acheteur)
    else Echec
        A->>U: Redirect connexion.php?error=...
    end
```

## Flux d'achat (3 modes)

```mermaid
flowchart TD
    START["Acheteur consulte un article"] --> TYPE{Type de vente ?}

    TYPE -->|Achat immediat| BUY["Ajouter au panier"]
    BUY --> CART["Panier"]
    CART --> PAY["Page paiement<br/>Adresse + Carte bancaire"]
    PAY --> VALID{"Carte valide<br/>en BDD ?"}
    VALID -->|Oui| ORDER["Commande creee<br/>Article marque vendu"]
    VALID -->|Non| ERROR["Erreur paiement"]
    ORDER --> NOTIF["Notification confirmation"]
    ORDER --> REDUC{"Total > 100 euros ?"}
    REDUC -->|"> 200 euros"| CARD20["Carte reduction 20%"]
    REDUC -->|"> 100 euros"| CARD10["Carte reduction 10%"]
    REDUC -->|"< 100 euros"| DONE["Termine"]

    TYPE -->|Negociation| NEGO["Acheteur propose un prix"]
    NEGO --> ROUND{"Round <= 5 ?"}
    ROUND -->|Oui| SELLER{"Vendeur repond"}
    SELLER -->|Accepte| ACCEPTED["Prix accorde enregistre<br/>Ajout au panier auto"]
    ACCEPTED --> CART
    SELLER -->|Refuse / Contre-offre| ROUND
    ROUND -->|"Non (max atteint)"| EXPIRE["Negociation expiree"]

    TYPE -->|Meilleure offre| BID["Acheteur definit prix max"]
    BID --> AUTO["Systeme auto-encherit<br/>prix concurrent + 1 euro"]
    AUTO --> END_DATE{"Date fin atteinte ?"}
    END_DATE -->|Non| WAIT["Attente autres encherisseurs"]
    WAIT --> AUTO
    END_DATE -->|Oui| WINNER{"Plus offrant ?"}
    WINNER -->|Oui| WON["Statut gagnant<br/>Prix paye enregistre<br/>Ajout au panier auto"]
    WON --> CART
    WINNER -->|Non| LOST["Enchere perdue"]

    style START fill:#e3f2fd,stroke:#1565c0
    style ORDER fill:#e8f5e9,stroke:#2e7d32
    style ERROR fill:#fce4ec,stroke:#c62828
    style EXPIRE fill:#fff3e0,stroke:#e65100
    style LOST fill:#fce4ec,stroke:#c62828
    style ACCEPTED fill:#e8f5e9,stroke:#2e7d32
    style WON fill:#e8f5e9,stroke:#2e7d32
```
