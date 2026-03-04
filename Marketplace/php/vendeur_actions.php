<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$action = $_POST['action'] ?? '';
$uid = $_SESSION['user_id'];

// Créer les répertoires s'ils n'existent pas
$photos_dir = '../images/vendeurs/photos';
$backgrounds_dir = '../images/vendeurs/backgrounds';

if (!is_dir($photos_dir)) {
    mkdir($photos_dir, 0755, true);
}
if (!is_dir($backgrounds_dir)) {
    mkdir($backgrounds_dir, 0755, true);
}

if ($action === 'upload_photo') {
    // Upload de la photo de profil
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement du fichier']);
        exit;
    }

    $file = $_FILES['photo'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5 MB

    // Vérifier le type MIME
    if (!in_array($file['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé (JPEG, PNG, GIF, WebP)']);
        exit;
    }

    // Vérifier la taille
    if ($file['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Fichier trop volumineux (max 5 MB)']);
        exit;
    }

    // Générer un nom unique
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'vendeur_' . $uid . '_' . time() . '.' . $ext;
    $filepath = $photos_dir . '/' . $filename;

    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        http_response_code(500);
        error_log("Upload failed - cannot move file from {$file['tmp_name']} to $filepath");
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde du fichier']);
        exit;
    }

    // Vérifier que le fichier a bien été créé
    if (!file_exists($filepath)) {
        http_response_code(500);
        error_log("Upload failed - file not found after move: $filepath");
        echo json_encode(['success' => false, 'message' => 'Erreur: fichier non trouvé après upload']);
        exit;
    }

    error_log("File uploaded successfully to: $filepath");

    // Supprimer l'ancienne photo si elle existe
    try {
        $old_photo = $pdo->prepare("SELECT photo_url FROM utilisateurs WHERE id = :uid");
        $old_photo->execute([':uid' => $uid]);
        $old_result = $old_photo->fetch();
        if ($old_result && $old_result['photo_url'] && file_exists('../' . $old_result['photo_url'])) {
            unlink('../' . $old_result['photo_url']);
        }
    } catch (Exception $e) {
        // Ignorer les erreurs de suppression
    }

    // Préparer le chemin de l'image (URL absolue pour éviter les problèmes de chemin relatif)
    $photo_url = '/images/vendeurs/photos/' . $filename;
    error_log("Database URL to save: $photo_url");

    // Mettre à jour la base de données
    try {
        error_log("Attempting to update database for user $uid with photo_url: $photo_url");
        $update = $pdo->prepare("UPDATE utilisateurs SET photo_url = :photo_url WHERE id = :uid");
        $update->execute([':photo_url' => $photo_url, ':uid' => $uid]);

        // Vérifier que l'update a bien eu lieu
        $rows = $update->rowCount();
        error_log("Database update result: $rows rows affected");
        
        if ($rows === 0) {
            // Aucune ligne affectée - peut-être que la colonne n'existe pas
            error_log("No rows updated - column may not exist");
            throw new PDOException("Aucune ligne mise à jour");
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Photo uploadée avec succès', 'photo_url' => $photo_url]);
        exit;
    } catch (PDOException $e) {
        error_log("Database error on update: " . $e->getMessage());
        http_response_code(500);
        // Essayer de créer la colonne si elle n'existe pas
        try {
            error_log("Attempting to add photo_url column if not exists");
            
            // Vérifier si la colonne existe déjà
            $checkColumn = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='utilisateurs' AND COLUMN_NAME='photo_url'");
            
            if ($checkColumn->rowCount() === 0) {
                error_log("Column photo_url doesn't exist, creating it");
                $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN photo_url VARCHAR(500) DEFAULT NULL");
            } else {
                error_log("Column photo_url already exists");
            }
            
            error_log("Column created/verified, retrying update");
            $update = $pdo->prepare("UPDATE utilisateurs SET photo_url = :photo_url WHERE id = :uid");
            $update->execute([':photo_url' => $photo_url, ':uid' => $uid]);
            
            error_log("Retry successful");
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Photo uploadée avec succès', 'photo_url' => $photo_url]);
            exit;
        } catch (PDOException $e2) {
            error_log("Database error on retry: " . $e2->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de la base de données']);
            exit;
        }
    }

} elseif ($action === 'upload_background') {
    // Upload de l'image de fond
    if (!isset($_FILES['background']) || $_FILES['background']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement du fichier']);
        exit;
    }

    $file = $_FILES['background'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 10 * 1024 * 1024; // 10 MB

    // Vérifier le type MIME
    if (!in_array($file['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé (JPEG, PNG, GIF, WebP)']);
        exit;
    }

    // Vérifier la taille
    if ($file['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Fichier trop volumineux (max 10 MB)']);
        exit;
    }

    // Générer un nom unique
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'background_' . $uid . '_' . time() . '.' . $ext;
    $filepath = $backgrounds_dir . '/' . $filename;

    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        http_response_code(500);
        error_log("Background upload failed - cannot move file from {$file['tmp_name']} to $filepath");
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde du fichier']);
        exit;
    }

    // Vérifier que le fichier a bien été créé
    if (!file_exists($filepath)) {
        http_response_code(500);
        error_log("Background upload failed - file not found after move: $filepath");
        echo json_encode(['success' => false, 'message' => 'Erreur: fichier non trouvé après upload']);
        exit;
    }

    error_log("Background file uploaded successfully to: $filepath");

    // Supprimer l'ancien background si elle existe
    try {
        $old_bg = $pdo->prepare("SELECT background_url FROM utilisateurs WHERE id = :uid");
        $old_bg->execute([':uid' => $uid]);
        $old_result = $old_bg->fetch();
        if ($old_result && $old_result['background_url'] && file_exists('../' . $old_result['background_url'])) {
            unlink('../' . $old_result['background_url']);
        }
    } catch (Exception $e) {
        // Ignorer les erreurs de suppression
    }

    // Préparer le chemin de l'image (URL absolue pour éviter les problèmes de chemin relatif)
    $background_url = '/images/vendeurs/backgrounds/' . $filename;
    error_log("Background URL to save: $background_url");

    // Mettre à jour la base de données
    try {
        error_log("Attempting to update background for user $uid with background_url: $background_url");
        $update = $pdo->prepare("UPDATE utilisateurs SET background_url = :background_url WHERE id = :uid");
        $update->execute([':background_url' => $background_url, ':uid' => $uid]);

        // Vérifier que l'update a bien eu lieu
        $rows = $update->rowCount();
        error_log("Database update result: $rows rows affected");
        
        if ($rows === 0) {
            // Aucune ligne affectée - peut-être que la colonne n'existe pas
            error_log("No rows updated - column may not exist");
            throw new PDOException("Aucune ligne mise à jour");
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Image de fond uploadée avec succès', 'background_url' => $background_url]);
        exit;
    } catch (PDOException $e) {
        error_log("Database error on update: " . $e->getMessage());
        http_response_code(500);
        // Essayer de créer la colonne si elle n'existe pas
        try {
            error_log("Attempting to add background_url column if not exists");
            
            // Vérifier si la colonne existe déjà
            $checkColumn = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='utilisateurs' AND COLUMN_NAME='background_url'");
            
            if ($checkColumn->rowCount() === 0) {
                error_log("Column background_url doesn't exist, creating it");
                $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN background_url VARCHAR(500) DEFAULT NULL");
            } else {
                error_log("Column background_url already exists");
            }
            
            error_log("Column created/verified, retrying update");
            $update = $pdo->prepare("UPDATE utilisateurs SET background_url = :background_url WHERE id = :uid");
            $update->execute([':background_url' => $background_url, ':uid' => $uid]);
            
            error_log("Retry successful");
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Image de fond uploadée avec succès', 'background_url' => $background_url]);
            exit;
        } catch (PDOException $e2) {
            error_log("Database error on retry: " . $e2->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de la base de données: ' . $e2->getMessage()]);
            exit;
        }
    }

} elseif ($action === 'delete_photo') {
    // Supprimer la photo de profil
    try {
        $photo = $pdo->prepare("SELECT photo_url FROM utilisateurs WHERE id = :uid");
        $photo->execute([':uid' => $uid]);
        $result = $photo->fetch();
        
        if ($result && $result['photo_url']) {
            $filepath = '../' . $result['photo_url'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        $update = $pdo->prepare("UPDATE utilisateurs SET photo_url = NULL WHERE id = :uid");
        $update->execute([':uid' => $uid]);

        echo json_encode(['success' => true, 'message' => 'Photo supprimée avec succès']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        exit;
    }

} elseif ($action === 'delete_background') {
    // Supprimer l'image de fond
    try {
        $bg = $pdo->prepare("SELECT background_url FROM utilisateurs WHERE id = :uid");
        $bg->execute([':uid' => $uid]);
        $result = $bg->fetch();
        
        if ($result && $result['background_url']) {
            $filepath = '../' . $result['background_url'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        $update = $pdo->prepare("UPDATE utilisateurs SET background_url = NULL WHERE id = :uid");
        $update->execute([':uid' => $uid]);

        echo json_encode(['success' => true, 'message' => 'Image de fond supprimée avec succès']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        exit;
    }

} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
}
?>
