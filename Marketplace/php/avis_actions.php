<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté.']);
    exit;
}

$action = $_POST['action'] ?? '';
$uid = $_SESSION['user_id'];

if ($action === 'add') {
    $article_id = (int)($_POST['article_id'] ?? 0);
    $note = (int)($_POST['rating'] ?? 5);
    $commentaire = trim($_POST['commentaire'] ?? '');

    if ($article_id <= 0 || !$commentaire) {
        echo json_encode(['success' => false, 'message' => 'Données invalides.']);
        exit;
    }

    $note = max(1, min(5, $note));

    try {
        // Vérifier que l'utilisateur n'a pas déjà laissé un avis
        $stmt = $pdo->prepare("SELECT id FROM avis WHERE article_id = :aid AND auteur_id = :uid");
        $stmt->execute([':aid' => $article_id, ':uid' => $uid]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Vous avez déjà laissé un avis sur cet article.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO avis (article_id, auteur_id, note, commentaire) VALUES (:aid, :uid, :note, :com)");
        $stmt->execute([':aid' => $article_id, ':uid' => $uid, ':note' => $note, ':com' => $commentaire]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
}
