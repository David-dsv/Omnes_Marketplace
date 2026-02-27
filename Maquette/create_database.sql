-- =============================================
-- Omnes MarketPlace - Script de création BDD
-- =============================================

-- Force l'encodage de la session d'import pour éviter la corruption des accents.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

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
    type_vente ENUM('achat_immediat', 'negociation', 'meilleure_offre') NOT NULL,
    gamme ENUM('regulier', 'haut_de_gamme', 'rare') NOT NULL DEFAULT 'regulier',
    image_url VARCHAR(500) DEFAULT 'images/articles/placeholder.png',
    statut ENUM('disponible', 'vendu', 'retire') NOT NULL DEFAULT 'disponible',
    date_debut_enchere DATETIME DEFAULT NULL,
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
    prix_negocie DECIMAL(10, 2) DEFAULT NULL,
    negociation_id INT DEFAULT NULL,
    enchere_id INT DEFAULT NULL,
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
-- Table : negociations
-- =============================================
CREATE TABLE negociations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    acheteur_id INT NOT NULL,
    vendeur_id INT NOT NULL,
    statut ENUM('en_cours', 'accepte', 'refuse', 'expire') NOT NULL DEFAULT 'en_cours',
    prix_accorde DECIMAL(10, 2) DEFAULT NULL,
    date_resolution DATETIME DEFAULT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_negociations_article_statut (article_id, statut),
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
    KEY idx_negociation_messages_nego_date (negociation_id, date_creation),
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
-- Table : encheres (meilleure offre)
-- =============================================
CREATE TABLE encheres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    acheteur_id INT NOT NULL,
    montant_max DECIMAL(10, 2) NOT NULL,
    prix_paye DECIMAL(10, 2) DEFAULT NULL,
    statut ENUM('en_attente', 'gagnant', 'perdant') NOT NULL DEFAULT 'en_attente',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enchere (article_id, acheteur_id),
    KEY idx_encheres_article_montant_date (article_id, montant_max, date_creation),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (acheteur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE panier
    ADD CONSTRAINT fk_panier_negociation
        FOREIGN KEY (negociation_id) REFERENCES negociations(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_panier_enchere
        FOREIGN KEY (enchere_id) REFERENCES encheres(id) ON DELETE SET NULL;
-- =============================================
-- DONNÉES D'EXEMPLE
-- =============================================

-- Comptes administrateurs de test (mots de passe en clair) :
-- - admin@omnesmarketplace.fr / admin123
-- - admin.hippolyte@edu.ece.fr / ECE Omnes 2026
INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, role) VALUES
('Admin', 'Omnes', 'admin@omnesmarketplace.fr', '$2y$12$3Z8YQD8ronGNz23lsddCSOpHwRTLjJni3hJCWw.G9y41TDPobeW9S', 'administrateur');

-- Comptes vendeurs de test (mot de passe en clair pour tous) : vendeur123
-- - marie.dupont@edu.ece.fr
-- - lucas.martin@edu.ece.fr
-- - sophie.leroy@edu.ece.fr
-- - adam.moreau@edu.ece.fr
-- - ines.garnier@edu.ece.fr
-- - kevin.roux@edu.ece.fr
-- - yasmine.faure@edu.ece.fr
INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, telephone, role) VALUES
('Marie', 'Dupont', 'marie.dupont@edu.ece.fr', '$2y$12$MkjqymHH4npGf306PL9AbOOwBq7Oc5mmQTopoxsICjvyuiagCU7lS', '06 12 34 56 78', 'vendeur'),
('Lucas', 'Martin', 'lucas.martin@edu.ece.fr', '$2y$12$MkjqymHH4npGf306PL9AbOOwBq7Oc5mmQTopoxsICjvyuiagCU7lS', '06 98 76 54 32', 'vendeur');

-- Comptes acheteurs de test (mot de passe en clair pour tous) : acheteur123
INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, telephone, adresse, role) VALUES
('Emma', 'Bernard', 'emma.bernard@edu.ece.fr', '$2y$12$nuQLR.rRVZiyptUP2vnoi.rZjYR94f9fr7Fh0ulWXvrgHcAU/N2Qq', '06 11 22 33 44', '15 Rue de la Paix, 75002 Paris', 'acheteur'),
('Thomas', 'Petit', 'thomas.petit@edu.ece.fr', '$2y$12$nuQLR.rRVZiyptUP2vnoi.rZjYR94f9fr7Fh0ulWXvrgHcAU/N2Qq', '06 55 66 77 88', '8 Avenue Montaigne, 75008 Paris', 'acheteur');

-- Administrateur supplementaire de test
INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, role) VALUES
('Admin', 'Hippolyte', 'admin.hippolyte@edu.ece.fr', '$2y$12$pG.ucW1MJfWJWULV8vKrXuZSgsc/pVZ37S/MK/b2fXcuKVlk3eWeu', 'administrateur');

-- Vendeurs supplementaires (mot de passe en clair : vendeur123)
INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, telephone, role) VALUES
('Sophie', 'Leroy', 'sophie.leroy@edu.ece.fr', '$2y$12$MkjqymHH4npGf306PL9AbOOwBq7Oc5mmQTopoxsICjvyuiagCU7lS', '06 44 11 22 33', 'vendeur'),
('Adam', 'Moreau', 'adam.moreau@edu.ece.fr', '$2y$12$MkjqymHH4npGf306PL9AbOOwBq7Oc5mmQTopoxsICjvyuiagCU7lS', '06 67 89 10 11', 'vendeur'),
('Ines', 'Garnier', 'ines.garnier@edu.ece.fr', '$2y$12$MkjqymHH4npGf306PL9AbOOwBq7Oc5mmQTopoxsICjvyuiagCU7lS', '06 21 32 43 54', 'vendeur'),
('Kevin', 'Roux', 'kevin.roux@edu.ece.fr', '$2y$12$MkjqymHH4npGf306PL9AbOOwBq7Oc5mmQTopoxsICjvyuiagCU7lS', '06 88 77 66 55', 'vendeur'),
('Yasmine', 'Faure', 'yasmine.faure@edu.ece.fr', '$2y$12$MkjqymHH4npGf306PL9AbOOwBq7Oc5mmQTopoxsICjvyuiagCU7lS', '06 56 34 12 90', 'vendeur');

-- Acheteurs supplementaires (mot de passe en clair : acheteur123)
INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, telephone, adresse, role) VALUES
('Clara', 'Robert', 'clara.robert@edu.ece.fr', '$2y$12$nuQLR.rRVZiyptUP2vnoi.rZjYR94f9fr7Fh0ulWXvrgHcAU/N2Qq', '06 40 11 22 33', '12 Rue des Ecoles, 75005 Paris', 'acheteur'),
('Hugo', 'Simon', 'hugo.simon@edu.ece.fr', '$2y$12$nuQLR.rRVZiyptUP2vnoi.rZjYR94f9fr7Fh0ulWXvrgHcAU/N2Qq', '06 40 22 33 44', '28 Rue Vaneau, 75007 Paris', 'acheteur'),
('Lea', 'Laurent', 'lea.laurent@edu.ece.fr', '$2y$12$nuQLR.rRVZiyptUP2vnoi.rZjYR94f9fr7Fh0ulWXvrgHcAU/N2Qq', '06 40 33 44 55', '4 Rue Oberkampf, 75011 Paris', 'acheteur'),
('Noah', 'Michel', 'noah.michel@edu.ece.fr', '$2y$12$nuQLR.rRVZiyptUP2vnoi.rZjYR94f9fr7Fh0ulWXvrgHcAU/N2Qq', '06 40 44 55 66', '22 Rue Legendre, 75017 Paris', 'acheteur'),
('Manon', 'Garcia', 'manon.garcia@edu.ece.fr', '$2y$12$nuQLR.rRVZiyptUP2vnoi.rZjYR94f9fr7Fh0ulWXvrgHcAU/N2Qq', '06 40 55 66 77', '41 Rue Lecourbe, 75015 Paris', 'acheteur'),
('Nathan', 'Chevalier', 'nathan.chevalier@edu.ece.fr', '$2y$12$nuQLR.rRVZiyptUP2vnoi.rZjYR94f9fr7Fh0ulWXvrgHcAU/N2Qq', '06 40 66 77 88', '6 Rue Monge, 75005 Paris', 'acheteur'),
('Sarah', 'Perrin', 'sarah.perrin@edu.ece.fr', '$2y$12$nuQLR.rRVZiyptUP2vnoi.rZjYR94f9fr7Fh0ulWXvrgHcAU/N2Qq', '06 40 77 88 99', '19 Rue de Charonne, 75011 Paris', 'acheteur'),
('Enzo', 'Renault', 'enzo.renault@edu.ece.fr', '$2y$12$nuQLR.rRVZiyptUP2vnoi.rZjYR94f9fr7Fh0ulWXvrgHcAU/N2Qq', '06 40 88 99 10', '33 Rue du Faubourg Saint-Denis, 75010 Paris', 'acheteur'),
('Camille', 'Girard', 'camille.girard@edu.ece.fr', '$2y$12$nuQLR.rRVZiyptUP2vnoi.rZjYR94f9fr7Fh0ulWXvrgHcAU/N2Qq', '06 40 99 10 21', '17 Rue Mouffetard, 75005 Paris', 'acheteur'),
('Julien', 'Blanchard', 'julien.blanchard@edu.ece.fr', '$2y$12$nuQLR.rRVZiyptUP2vnoi.rZjYR94f9fr7Fh0ulWXvrgHcAU/N2Qq', '06 40 10 21 32', '9 Boulevard Voltaire, 75011 Paris', 'acheteur'),
('Zoe', 'Fontaine', 'zoe.fontaine@edu.ece.fr', '$2y$12$nuQLR.rRVZiyptUP2vnoi.rZjYR94f9fr7Fh0ulWXvrgHcAU/N2Qq', '06 40 21 32 43', '2 Rue de Turenne, 75004 Paris', 'acheteur'),
('Maxime', 'Rolland', 'maxime.rolland@edu.ece.fr', '$2y$12$nuQLR.rRVZiyptUP2vnoi.rZjYR94f9fr7Fh0ulWXvrgHcAU/N2Qq', '06 40 32 43 54', '27 Rue de la Roquette, 75011 Paris', 'acheteur'),
('Anais', 'Marchand', 'anais.marchand@edu.ece.fr', '$2y$12$nuQLR.rRVZiyptUP2vnoi.rZjYR94f9fr7Fh0ulWXvrgHcAU/N2Qq', '06 40 43 54 65', '14 Rue des Martyrs, 75009 Paris', 'acheteur');

-- Vendeur de test principal (connexion rapide)
-- Identifiants : vendeur@vendeur.com / vendeur123
-- date_creation forcée dans le futur pour apparaître en tête de "Gestion des vendeurs" (tri DESC)
INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, telephone, role, date_creation) VALUES
('Bastien', 'Delorme', 'vendeur@vendeur.com', '$2y$12$MkjqymHH4npGf306PL9AbOOwBq7Oc5mmQTopoxsICjvyuiagCU7lS', '06 00 00 00 01', 'vendeur', DATE_ADD(NOW(), INTERVAL 5 MINUTE));

-- Articles d'exemple
INSERT INTO articles (vendeur_id, titre, description, prix, categorie, type_vente, gamme, image_url) VALUES
(2, 'MacBook Pro M3 2024', 'MacBook Pro 14 pouces avec puce M3, 16 Go RAM, 512 Go SSD. Excellent état, utilisé 6 mois.', 1499.00, 'Électronique', 'achat_immediat', 'haut_de_gamme', 'images/articles/macm3.jpg'),
(2, 'Lot de livres Informatique', '5 livres de programmation : Python, Java, C++, Algorithmes, Base de données. Parfait pour les étudiants ECE.', 45.00, 'Livres', 'achat_immediat', 'regulier', 'images/articles/lotlivreinformatique.jpg'),
(2, 'Vélo de course Specialized', 'Vélo de course Specialized Allez en carbone. Taille M, très bon état.', 800.00, 'Sports', 'negociation', 'haut_de_gamme', 'images/articles/velo-de-route-specialized-tarmac-expert-ultegra-di2-54.jpg'),
(3, 'iPhone 15 Pro Max 256 Go', 'iPhone 15 Pro Max couleur titane, 256 Go. Sous garantie Apple.', 950.00, 'Électronique', 'achat_immediat', 'haut_de_gamme', 'images/articles/Apple-iPhone-15-Pro-Max-6-7-5G-Double-SIM-256-Go-Bleu-Titanium.jpg'),
(3, 'Bureau ergonomique IKEA', 'Bureau réglable en hauteur IKEA BEKANT 160x80 cm. Blanc, très bon état.', 150.00, 'Maison', 'achat_immediat', 'regulier', 'images/articles/placeholder.png'),
(3, 'Montre Casio vintage rare', 'Casio A168WA édition limitée dorée. Neuve dans son emballage d''origine.', 250.00, 'Divers', 'negociation', 'rare', 'images/articles/casio.jpg'),
(2, 'Veste North Face Nuptse', 'Doudoune North Face Nuptse 1996 noire, taille L. Portée 2 fois.', 180.00, 'Vêtements', 'negociation', 'regulier', 'images/articles/placeholder.png'),
(3, 'Calculatrice TI-83 Premium', 'Calculatrice Texas Instruments TI-83 Premium CE. Parfaite pour les cours de maths.', 60.00, 'Électronique', 'achat_immediat', 'regulier', 'images/articles/placeholder.png'),
(2, 'Adidas Muenchen pointure 40', 'Paire Adidas Muenchen pointure 40 en bon état général. Confortable et idéale pour un usage quotidien.', 120.00, 'Vêtements', 'achat_immediat', 'regulier', 'images/articles/Adidas Muenchen pointure 40.png'),
(3, 'Chaussures Palladium T 37', 'Chaussures Palladium taille 37, propres et prêtes à porter. Style urbain et semelle robuste.', 70.00, 'Vêtements', 'achat_immediat', 'regulier', 'images/articles/Chaussures Palladium T 37.png'),
(7, 'Chemise femme PROMOD taille 38', 'Chemise femme marque PROMOD taille 38. Coupe moderne et tissu léger.', 35.00, 'Vêtements', 'achat_immediat', 'regulier', 'images/articles/Chemise femme marque PROMOD taille 38.png'),
(8, 'Sculpture décoration loup hurlant', 'Figurine décorative loup hurlant, idéale pour un salon ou un bureau. Objet de décoration original.', 55.00, 'Maison', 'achat_immediat', 'rare', 'images/articles/Decoration loup hurlant sculpture figurine.png'),
(9, 'Enceinte Marshall', 'Enceinte Marshall en excellent état de fonctionnement. Son puissant et design iconique.', 200.00, 'Électronique', 'achat_immediat', 'haut_de_gamme', 'images/articles/Enceinte Marshall (200e).png'),
(10, 'Huawei Pura 70', 'Smartphone Huawei Pura 70, appareil propre et fonctionnel. Vente avec possibilité de négociation.', 699.00, 'Électronique', 'negociation', 'haut_de_gamme', 'images/articles/Échange Huawei Pura 70 (699e).png'),
(11, 'Fauteuil à retapisser', 'Fauteuil à retapisser, structure saine. Projet parfait pour customisation ou rénovation.', 80.00, 'Maison', 'negociation', 'regulier', 'images/articles/Fauteuil a retapisser.png'),
(2, 'Jean Levi''s motif W31 L29', 'Jean Levi''s W31 L29 avec motif. Très bon état, coupe confortable.', 65.00, 'Vêtements', 'achat_immediat', 'regulier', 'images/articles/Jean Levi''s à motif W31 L29.png'),
(3, 'Livre d''entraînement Sciences de l''Ingénieur', 'Ouvrage d''exercices en sciences de l''ingénieur, utile pour révisions et entraînement.', 22.00, 'Livres', 'achat_immediat', 'regulier', 'images/articles/Livre entrainement exo - de Sciences de l''Ingénieur.png'),
(7, 'Panier de basket', 'Panier de basket pour entraînement extérieur ou intérieur. Matériel solide et simple à installer.', 110.00, 'Sports', 'achat_immediat', 'regulier', 'images/articles/Panier de basket.png'),
(8, 'Raquette de badminton', 'Raquette de badminton légère et maniable, adaptée aux débutants comme aux joueurs réguliers.', 45.00, 'Sports', 'achat_immediat', 'regulier', 'images/articles/Raquette, badminton.png'),
(9, 'Samsung Galaxy Note 10 Lite', 'Samsung Galaxy Note 10 Lite en bon état. Batterie correcte et appareil pleinement utilisable.', 159.00, 'Électronique', 'achat_immediat', 'haut_de_gamme', 'images/articles/SAMSUNG Galaxy Note 10 Lite (159e).png'),
(10, 'Vélo VAE Lapierre Xeluis 600', 'Vélo à assistance électrique Lapierre Xeluis 600 avec moteur Fazua. Modèle performant.', 1850.00, 'Sports', 'negociation', 'haut_de_gamme', 'images/articles/Vend velo VAE Lapierre Xeluis 600 moteur FAZUA.png'),
(11, 'Aspirateur robot', 'Aspirateur robot fonctionnel pour entretien quotidien du sol. Utilisation simple et efficace.', 140.00, 'Maison', 'achat_immediat', 'regulier', 'images/articles/aspirateur robot.png'),
(2, 'Banc de musculation développé couché', 'Banc de musculation pour développé couché, stable et robuste. Idéal home gym.', 260.00, 'Sports', 'negociation', 'haut_de_gamme', 'images/articles/banc muscu dev couche.png'),
(3, 'Buffet déco', 'Buffet déco en bon état, parfait pour rangement de salon ou salle à manger.', 190.00, 'Maison', 'negociation', 'regulier', 'images/articles/buffet deco.png'),
(7, 'Chaises tournantes (lot)', 'Lot de chaises tournantes confortables. Convient pour bureau ou coin repas.', 150.00, 'Maison', 'achat_immediat', 'regulier', 'images/articles/chaises tournantes.png'),
(8, 'Cage à lapin', 'Cage à lapin propre et prête à l''emploi. Accessoire pratique pour petit animal.', 40.00, 'Divers', 'achat_immediat', 'regulier', 'images/articles/divers - cage lapin.png'),
(9, 'Distributeur machine à café', 'Distributeur machine à café en état de marche. Idéal pour espace commun ou colocation.', 300.00, 'Divers', 'negociation', 'regulier', 'images/articles/divers - distributeur machine cafe 300e.png'),
(10, 'Nettoyeur d''oreille', 'Petit appareil nettoyeur d''oreille en bon état. Pratique pour un usage ponctuel.', 20.00, 'Divers', 'achat_immediat', 'regulier', 'images/articles/divers - netoyeur oreille.png'),
(11, 'Déambulateur 3 roues', 'Déambulateur 3 roues stable et facile à manœuvrer. Bon état global.', 95.00, 'Divers', 'negociation', 'regulier', 'images/articles/divers deambulateur 3 roues.png'),
(2, 'Aquarium 54L', 'Aquarium 54 litres pour poisson d''eau douce. Idéal pour débuter un petit bac.', 75.00, 'Divers', 'achat_immediat', 'regulier', 'images/articles/divers- aquarium 54L.png'),
(3, 'Enceinte JBL Charge 5', 'Enceinte portable JBL Charge 5, son puissant et bonne autonomie.', 99.00, 'Électronique', 'achat_immediat', 'haut_de_gamme', 'images/articles/enceinteJBL charge5.png'),
(7, 'Livre INSA - Fabriquer le monde', 'Livre orienté ingénierie et innovation, bon support de lecture pour étudiants.', 18.00, 'Livres', 'achat_immediat', 'regulier', 'images/articles/livre Ingénieurs INSA - Fabriquer le monde.png'),
(8, 'BD Où est Charlie ?', 'Bande dessinée Où est Charlie ?, exemplaire en bon état.', 12.00, 'Livres', 'achat_immediat', 'regulier', 'images/articles/livre bd - ou est charlie.png'),
(9, 'Lot de livres policiers', 'Lot de romans policiers, parfait pour amateurs de suspense.', 28.00, 'Livres', 'achat_immediat', 'regulier', 'images/articles/livres policiers lot.png'),
(10, 'Raquette de tennis', 'Raquette de tennis en bon état pour entraînement et matchs loisirs.', 58.00, 'Sports', 'achat_immediat', 'regulier', 'images/articles/raquette tennis.png');

-- Articles dédiés au vendeur de test (achat immédiat + négociation)
INSERT INTO articles (vendeur_id, titre, description, prix, categorie, type_vente, gamme, image_url) VALUES
((SELECT id FROM utilisateurs WHERE email = 'vendeur@vendeur.com' LIMIT 1), 'Ordinateur portable Lenovo ThinkPad T14', 'Lenovo ThinkPad T14 en très bon état, batterie solide, idéal pour travail et cours.', 680.00, 'Électronique', 'achat_immediat', 'haut_de_gamme', 'images/articles/lenovo.png'),
((SELECT id FROM utilisateurs WHERE email = 'vendeur@vendeur.com' LIMIT 1), 'Chaise de bureau ergonomique noire', 'Chaise ergonomique avec soutien lombaire, hauteur réglable, usure légère.', 95.00, 'Maison', 'negociation', 'regulier', 'images/articles/lachaisebureau.png'),
((SELECT id FROM utilisateurs WHERE email = 'vendeur@vendeur.com' LIMIT 1), 'Lot de manuels de mathématiques', 'Lot de 4 manuels de maths (analyse et algèbre), parfait pour révisions.', 40.00, 'Livres', 'achat_immediat', 'regulier', 'images/articles/lotManuelmaths.png');

-- Articles meilleure offre (enchères)
INSERT INTO articles (vendeur_id, titre, description, prix, categorie, type_vente, gamme, image_url, date_debut_enchere, date_fin_enchere) VALUES
(2, 'Bague Cartier 18 carats or jaune', 'Bague de marque Cartier, 18 carats d''or jaune, pesant 4.8 grammes. Pièce rare et authentique avec certificat.', 500.00, 'Divers', 'meilleure_offre', 'rare', 'images/articles/baguecartier.png', '2026-03-01 09:00:00', '2026-03-15 17:00:00'),
(3, 'Montre Omega Seamaster vintage', 'Montre Omega Seamaster des années 70, mouvement automatique, cadran bleu. Pièce de collection en état remarquable.', 1200.00, 'Divers', 'meilleure_offre', 'rare', 'images/articles/montreomega.png', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 7 DAY)),
(7, 'Premier tirage Harry Potter français', 'Harry Potter à l''école des sorciers, premier tirage Gallimard 1998. Couverture rigide, état quasi neuf.', 300.00, 'Livres', 'meilleure_offre', 'rare', 'images/articles/harrypotterfolio.png', '2026-02-27 00:00:00', '2026-03-10 23:59:00'),
((SELECT id FROM utilisateurs WHERE email = 'vendeur@vendeur.com' LIMIT 1), 'Montre Seiko 5 vintage', 'Montre Seiko 5 automatique vintage, bracelet acier, bon état général. Pièce recherchée.', 220.00, 'Divers', 'meilleure_offre', 'rare', 'images/articles/montreseiko.png', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 7 DAY));

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
