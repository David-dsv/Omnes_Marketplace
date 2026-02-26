-- =============================================
-- Omnes MarketPlace - Script de création BDD
-- =============================================

DROP DATABASE IF EXISTS omnes_marketplace;
CREATE DATABASE omnes_marketplace CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE omnes_marketplace;

-- =============================================
-- Table : utilisateurs
-- =============================================
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prenom VARCHAR(100) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20) DEFAULT NULL,
    adresse TEXT DEFAULT NULL,
    role ENUM('acheteur', 'vendeur', 'administrateur') NOT NULL DEFAULT 'acheteur',
    actif TINYINT(1) NOT NULL DEFAULT 1,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- Table : articles
-- =============================================
CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendeur_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    prix DECIMAL(10, 2) NOT NULL,
    categorie ENUM('Électronique', 'Vêtements', 'Maison', 'Livres', 'Sports', 'Divers') NOT NULL,
    type_vente ENUM('achat_immediat', 'negociation', 'enchere') NOT NULL,
    gamme ENUM('regulier', 'haut_de_gamme', 'rare') NOT NULL DEFAULT 'regulier',
    image_url VARCHAR(500) DEFAULT 'images/placeholder.png',
    statut ENUM('disponible', 'vendu', 'retire') NOT NULL DEFAULT 'disponible',
    date_fin_enchere DATETIME DEFAULT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendeur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table : panier
-- =============================================
CREATE TABLE panier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    article_id INT NOT NULL,
    quantite INT NOT NULL DEFAULT 1,
    date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_panier (utilisateur_id, article_id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table : commandes
-- =============================================
CREATE TABLE commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    acheteur_id INT NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    adresse_livraison TEXT NOT NULL,
    statut ENUM('en_attente', 'confirmee', 'expediee', 'livree', 'annulee') NOT NULL DEFAULT 'en_attente',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (acheteur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table : commande_articles
-- =============================================
CREATE TABLE commande_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    article_id INT NOT NULL,
    prix DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table : encheres
-- =============================================
CREATE TABLE encheres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    acheteur_id INT NOT NULL,
    montant DECIMAL(10, 2) NOT NULL,
    montant_max DECIMAL(10, 2) NOT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (acheteur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table : negociations
-- =============================================
CREATE TABLE negociations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    acheteur_id INT NOT NULL,
    vendeur_id INT NOT NULL,
    statut ENUM('en_cours', 'accepte', 'refuse', 'expire') NOT NULL DEFAULT 'en_cours',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (acheteur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (vendeur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table : negociation_messages
-- =============================================
CREATE TABLE negociation_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    negociation_id INT NOT NULL,
    auteur_id INT NOT NULL,
    montant_propose DECIMAL(10, 2) DEFAULT NULL,
    message TEXT DEFAULT NULL,
    statut ENUM('en_attente', 'accepte', 'refuse') NOT NULL DEFAULT 'en_attente',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (negociation_id) REFERENCES negociations(id) ON DELETE CASCADE,
    FOREIGN KEY (auteur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table : avis
-- =============================================
CREATE TABLE avis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    auteur_id INT NOT NULL,
    note TINYINT NOT NULL CHECK (note BETWEEN 1 AND 5),
    commentaire TEXT NOT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_avis (article_id, auteur_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (auteur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table : notifications
-- =============================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    message TEXT NOT NULL,
    lue TINYINT(1) NOT NULL DEFAULT 0,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table : alertes_recherche
-- =============================================
CREATE TABLE alertes_recherche (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    mot_cle VARCHAR(255) NOT NULL,
    categorie ENUM('Électronique', 'Vêtements', 'Maison', 'Livres', 'Sports', 'Divers') DEFAULT NULL,
    prix_max DECIMAL(10, 2) DEFAULT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table : cartes_bancaires (simulation)
-- =============================================
CREATE TABLE cartes_bancaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_carte VARCHAR(20) NOT NULL,
    expiration VARCHAR(5) NOT NULL,
    cvv VARCHAR(4) NOT NULL,
    titulaire VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- =============================================
-- Table : cartes_reduction (bonus)
-- =============================================
CREATE TABLE cartes_reduction (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    pourcentage INT NOT NULL,
    commande_id INT NOT NULL,
    utilisee TINYINT(1) NOT NULL DEFAULT 0,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- DONNÉES D'EXEMPLE
-- =============================================

-- Admin par défaut (mot de passe : admin123)
INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, role) VALUES
('Admin', 'Omnes', 'admin@omnesmarketplace.fr', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOXXJoQBFJ1TfBKdVxJFPbQZz3FVzHYC6', 'administrateur');

-- Vendeurs (mot de passe : vendeur123)
INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, telephone, role) VALUES
('Marie', 'Dupont', 'marie.dupont@edu.ece.fr', '$2y$10$YgS4UxUFJH5Rn4K5Ws7OWeRfMFGDqXSfGJ5.8iVk0lHfXmPnXqGjy', '06 12 34 56 78', 'vendeur'),
('Lucas', 'Martin', 'lucas.martin@edu.ece.fr', '$2y$10$YgS4UxUFJH5Rn4K5Ws7OWeRfMFGDqXSfGJ5.8iVk0lHfXmPnXqGjy', '06 98 76 54 32', 'vendeur');

-- Acheteurs (mot de passe : acheteur123)
INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, telephone, adresse, role) VALUES
('Emma', 'Bernard', 'emma.bernard@edu.ece.fr', '$2y$10$wEDskPO.g4O5iQjS3F2jFuYX8K7VvM3tZ.Q8K6bC4fLjDf4N5hCma', '06 11 22 33 44', '15 Rue de la Paix, 75002 Paris', 'acheteur'),
('Thomas', 'Petit', 'thomas.petit@edu.ece.fr', '$2y$10$wEDskPO.g4O5iQjS3F2jFuYX8K7VvM3tZ.Q8K6bC4fLjDf4N5hCma', '06 55 66 77 88', '8 Avenue Montaigne, 75008 Paris', 'acheteur');

-- Articles d'exemple
INSERT INTO articles (vendeur_id, titre, description, prix, categorie, type_vente, gamme) VALUES
(2, 'MacBook Pro M3 2024', 'MacBook Pro 14 pouces avec puce M3, 16 Go RAM, 512 Go SSD. Excellent état, utilisé 6 mois.', 1499.00, 'Électronique', 'achat_immediat', 'haut_de_gamme'),
(2, 'Lot de livres Informatique', '5 livres de programmation : Python, Java, C++, Algorithmes, Base de données. Parfait pour les étudiants ECE.', 45.00, 'Livres', 'achat_immediat', 'regulier'),
(2, 'Vélo de course Specialized', 'Vélo de course Specialized Allez en carbone. Taille M, très bon état.', 800.00, 'Sports', 'negociation', 'haut_de_gamme'),
(3, 'iPhone 15 Pro Max 256 Go', 'iPhone 15 Pro Max couleur titane, 256 Go. Sous garantie Apple.', 950.00, 'Électronique', 'enchere', 'haut_de_gamme'),
(3, 'Bureau ergonomique IKEA', 'Bureau réglable en hauteur IKEA BEKANT 160x80 cm. Blanc, très bon état.', 150.00, 'Maison', 'achat_immediat', 'regulier'),
(3, 'Montre Casio vintage rare', 'Casio A168WA édition limitée dorée. Neuve dans son emballage d''origine.', 250.00, 'Divers', 'enchere', 'rare'),
(2, 'Veste North Face Nuptse', 'Doudoune North Face Nuptse 1996 noire, taille L. Portée 2 fois.', 180.00, 'Vêtements', 'negociation', 'regulier'),
(3, 'Calculatrice TI-83 Premium', 'Calculatrice Texas Instruments TI-83 Premium CE. Parfaite pour les cours de maths.', 60.00, 'Électronique', 'achat_immediat', 'regulier');

-- Mise à jour des dates fin enchère pour les articles en enchère
UPDATE articles SET date_fin_enchere = DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE type_vente = 'enchere';

-- Cartes bancaires de test (simulation)
INSERT INTO cartes_bancaires (numero_carte, expiration, cvv, titulaire) VALUES
('4111111111111111', '12/28', '123', 'Emma Bernard'),
('5500000000000004', '06/27', '456', 'Thomas Petit'),
('3400000000000009', '09/29', '789', 'Test Carte');

-- Quelques avis d'exemple
INSERT INTO avis (article_id, auteur_id, note, commentaire) VALUES
(1, 4, 5, 'Excellent MacBook, comme neuf ! Vendeur très réactif.'),
(2, 5, 4, 'Bons livres, un peu jaunis mais contenu top pour les cours.'),
(5, 4, 5, 'Bureau parfait pour travailler, montage facile.');

-- Notifications d'exemple
INSERT INTO notifications (utilisateur_id, message) VALUES
(4, 'Bienvenue sur Omnes MarketPlace ! Découvrez nos articles.'),
(5, 'Bienvenue sur Omnes MarketPlace ! Découvrez nos articles.');
