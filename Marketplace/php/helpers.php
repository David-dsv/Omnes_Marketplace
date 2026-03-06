<?php
/**
 * Shared helper functions and constants for Omnes MarketPlace
 */

// ---- Constants ----

const ROLE_ADMIN = 'administrateur';
const ROLE_VENDEUR = 'vendeur';
const ROLE_ACHETEUR = 'acheteur';

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
