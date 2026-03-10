<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'vendeur') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$action = $_POST['action'] ?? '';
$uid = (int)$_SESSION['user_id'];

/**
 * Upload une image (photo ou background) pour le vendeur.
 */
function handle_upload(PDO $pdo, int $uid, string $file_key, string $column, string $dir, string $prefix, int $max_size): void
{
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement du fichier']);
        exit;
    }

    $file = $_FILES[$file_key];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    $real_type = mime_content_type($file['tmp_name']);
    if (!in_array($real_type, $allowed_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé (JPEG, PNG, GIF, WebP)']);
        exit;
    }

    if ($file['size'] > $max_size) {
        $max_mb = $max_size / (1024 * 1024);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Fichier trop volumineux (max {$max_mb} MB)"]);
        exit;
    }

    $upload_dir = __DIR__ . '/../images/vendeurs/' . $dir;
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, ALLOWED_IMAGE_EXTENSIONS)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Extension de fichier non autorisée']);
        exit;
    }
    
    $filename = $prefix . '_' . $uid . '_' . time() . '.' . $ext;
    $filepath = $upload_dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde du fichier']);
        exit;
    }

    // Supprimer l'ancien fichier
    try {
        $stmt = $pdo->prepare("SELECT $column FROM utilisateurs WHERE id = :uid");
        $stmt->execute([':uid' => $uid]);
        $old_url = $stmt->fetchColumn();
        if ($old_url) {
            $old_path = __DIR__ . '/../' . $old_url;
            if (file_exists($old_path)) {
                unlink($old_path);
            }
        }
    } catch (Exception $e) {
        // Ignorer les erreurs de suppression de l'ancien fichier
    }

    $new_url = '/images/vendeurs/' . $dir . '/' . $filename;

    try {
        $stmt = $pdo->prepare("UPDATE utilisateurs SET $column = :url WHERE id = :uid");
        $stmt->execute([':url' => $new_url, ':uid' => $uid]);

        echo json_encode(['success' => true, 'message' => 'Image uploadée avec succès', $column => $new_url]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour en base de données']);
    }
    exit;
}

/**
 * Supprime une image (photo ou background) du vendeur.
 */
function handle_delete(PDO $pdo, int $uid, string $column): void
{
    try {
        $stmt = $pdo->prepare("SELECT $column FROM utilisateurs WHERE id = :uid");
        $stmt->execute([':uid' => $uid]);
        $url = $stmt->fetchColumn();

        if ($url) {
            $path = __DIR__ . '/../' . $url;
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $stmt = $pdo->prepare("UPDATE utilisateurs SET $column = NULL WHERE id = :uid");
        $stmt->execute([':uid' => $uid]);

        echo json_encode(['success' => true, 'message' => 'Image supprimée avec succès']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
    }
    exit;
}

switch ($action) {
    case 'upload_photo':
        handle_upload($pdo, $uid, 'photo', 'photo_url', 'photos', 'vendeur', 5 * 1024 * 1024);
        break;
    case 'upload_background':
        handle_upload($pdo, $uid, 'background', 'background_url', 'backgrounds', 'background', 10 * 1024 * 1024);
        break;
    case 'delete_photo':
        handle_delete($pdo, $uid, 'photo_url');
        break;
    case 'delete_background':
        handle_delete($pdo, $uid, 'background_url');
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
}
