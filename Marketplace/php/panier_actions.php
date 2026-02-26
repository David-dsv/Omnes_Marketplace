<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$uid = $_SESSION['user_id'];

switch ($action) {
    case 'count':
        try {
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantite), 0) AS count FROM panier WHERE utilisateur_id = :uid");
            $stmt->execute([':uid' => $uid]);
            $result = $stmt->fetch();
            echo json_encode(['count' => (int)$result['count']]);
        } catch (PDOException $e) {
            echo json_encode(['count' => 0]);
        }
        break;

    case 'add':
        $article_id = (int)($_POST['article_id'] ?? 0);
        if ($article_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Article invalide.']);
            exit;
        }

        try {
            // Vérifier que l'article existe et est en achat immédiat
            $stmt = $pdo->prepare("SELECT id FROM articles WHERE id = :id AND statut = 'disponible' AND type_vente = 'achat_immediat'");
            $stmt->execute([':id' => $article_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Article non disponible.']);
                exit;
            }

            // Vérifier si déjà dans le panier
            $stmt = $pdo->prepare("SELECT id FROM panier WHERE utilisateur_id = :uid AND article_id = :aid");
            $stmt->execute([':uid' => $uid, ':aid' => $article_id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Article déjà dans le panier.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO panier (utilisateur_id, article_id, quantite) VALUES (:uid, :aid, 1)");
            $stmt->execute([':uid' => $uid, ':aid' => $article_id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
        }
        break;

    case 'remove':
        $article_id = (int)($_POST['article_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM panier WHERE utilisateur_id = :uid AND article_id = :aid");
            $stmt->execute([':uid' => $uid, ':aid' => $article_id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
}
