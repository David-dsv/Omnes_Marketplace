<?php
/**
 * Shared helper functions and constants for Omnes MarketPlace
 */

// ---- Constants ----

const ROLE_ADMIN = 'administrateur';
const ROLE_VENDEUR = 'vendeur';
const ROLE_ACHETEUR = 'acheteur';

const MIN_PASSWORD_LENGTH = 8;
const MAX_ADDRESS_LENGTH = 100;

const CATEGORIES = ['Électronique', 'Vêtements', 'Maison', 'Livres', 'Sports', 'Divers'];

const TYPE_VENTE_LABELS = [
    'achat_immediat' => 'Achat immédiat',
    'negociation'    => 'Négociation',
    'meilleure_offre' => 'Meilleure offre',
];

const GAMME_LABELS = [
    'regulier'       => 'Article régulier',
    'haut_de_gamme'  => 'Haut de gamme',
    'rare'           => 'Article rare',
];

const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// ---- JSON helpers ----

/**
 * Send a JSON error response and exit.
 */
function json_error(string $message): void
{
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

/**
 * Send a JSON success response and exit.
 */
function json_success(string $message, array $extra = []): void
{
    echo json_encode(array_merge(['success' => true, 'message' => $message], $extra));
    exit;
}

// ---- Notification helper ----

/**
 * Insert a notification for a user.
 */
function insert_notification(PDO $pdo, int $utilisateur_id, string $message): void
{
    $stmt = $pdo->prepare('INSERT INTO notifications (utilisateur_id, message) VALUES (:uid, :msg)');
    $stmt->execute([':uid' => $utilisateur_id, ':msg' => $message]);
}

// ---- Validation helpers ----

/**
 * Validate email format.
 */
function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number format (France: 9-10 digits).
 */
function is_valid_phone(string $phone): bool
{
    $phone = preg_replace('/\D/', '', $phone);
    return strlen($phone) >= 9 && strlen($phone) <= 10;
}

/**
 * Validate postal code (France: 5 digits).
 */
function is_valid_postal_code(string $code): bool
{
    return preg_match('/^\d{5}$/', $code) === 1;
}

/**
 * Validate password strength.
 */
function is_valid_password(string $password): bool
{
    return strlen($password) >= MIN_PASSWORD_LENGTH;
}

/**
 * Validate credit card using Luhn algorithm.
 */
function is_valid_credit_card(string $card_number): bool
{
    $card = preg_replace('/\D/', '', $card_number);
    return strlen($card) >= 13 && strlen($card) <= 19;
}

/**
 * Validate URL (HTTP/HTTPS only).
 */
function is_valid_url(string $url): bool
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false && 
           preg_match('/^https?:\/\//', $url) === 1;
}

/**
 * Validate uploaded image file.
 */
function is_valid_image_file(array $file, int $max_size_mb = 5): array
{
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Erreur lors du téléchargement du fichier';
        return $errors;
    }
    
    $max_bytes = $max_size_mb * 1024 * 1024;
    if ($file['size'] > $max_bytes) {
        $errors[] = "Le fichier dépasse la taille limite ({$max_size_mb} MB)";
        return $errors;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_EXTENSIONS, true)) {
        $errors[] = 'Format de fichier non autorisé (JPEG, PNG, GIF, WebP seulement)';
        return $errors;
    }
    
    $mime = mime_content_type($file['tmp_name']);
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowed_mimes, true)) {
        $errors[] = 'Le fichier n\'est pas une image valide';
        return $errors;
    }
    
    return $errors;
}
