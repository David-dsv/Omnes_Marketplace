<?php
/**
 * Initialisation de la base de données
 * S'assure que les colonnes de photos de vendeur existent
 */
require_once dirname(__DIR__) . '/config/database.php';

try {
    // Vérifier et créer les colonnes photo_url et background_url si elles n'existent pas
    // Compatible avec les anciennes versions de MySQL (avant 8.0.13)
    
    $checkPhotoColumn = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='utilisateurs' AND COLUMN_NAME='photo_url'");
    if ($checkPhotoColumn->rowCount() === 0) {
        $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN photo_url VARCHAR(500) DEFAULT NULL");
    }
    
    $checkBackgroundColumn = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='utilisateurs' AND COLUMN_NAME='background_url'");
    if ($checkBackgroundColumn->rowCount() === 0) {
        $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN background_url VARCHAR(500) DEFAULT NULL");
    }
    
    // Silencieusement continuer - pas d'erreur si les colonnes existent déjà
} catch (PDOException $e) {
    // Les colonnes existent probablement déjà, ignorer l'erreur
}
?>
