<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté.']);
    exit;
}

$action = $_POST['action'] ?? '';
$uid = $_SESSION['user_id'];

switch ($action) {
    case 'send_offer':
        $article_id = (int)($_POST['article_id'] ?? 0);
        $montant = (float)($_POST['montant'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        if ($article_id <= 0 || $montant <= 0) {
            echo json_encode(['success' => false, 'message' => 'Données invalides.']);
            exit;
        }

        try {
            // Vérifier l'article
            $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = :id AND type_vente = 'negociation' AND statut = 'disponible'");
            $stmt->execute([':id' => $article_id]);
            $article = $stmt->fetch();

            if (!$article) {
                echo json_encode(['success' => false, 'message' => 'Article non disponible.']);
                exit;
            }

            // Récupérer ou créer la négociation
            $stmt = $pdo->prepare("SELECT * FROM negociations WHERE article_id = :aid AND acheteur_id = :uid");
            $stmt->execute([':aid' => $article_id, ':uid' => $uid]);
            $negociation = $stmt->fetch();

            if (!$negociation) {
                $stmt = $pdo->prepare("INSERT INTO negociations (article_id, acheteur_id, vendeur_id, statut) VALUES (:aid, :uid, :vid, 'en_cours')");
                $stmt->execute([':aid' => $article_id, ':uid' => $uid, ':vid' => $article['vendeur_id']]);
                $nego_id = $pdo->lastInsertId();
            } else {
                $nego_id = $negociation['id'];

                // Vérifier le statut
                if ($negociation['statut'] !== 'en_cours') {
                    echo json_encode(['success' => false, 'message' => 'Négociation terminée.']);
                    exit;
                }
            }

            // Vérifier le nombre de rounds (max 5)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM negociation_messages WHERE negociation_id = :nid AND auteur_id = :uid");
            $stmt->execute([':nid' => $nego_id, ':uid' => $uid]);
            $nb_offers = $stmt->fetchColumn();

            if ($nb_offers >= 5) {
                echo json_encode(['success' => false, 'message' => 'Nombre maximum de rounds atteint (5).']);
                exit;
            }

            // Enregistrer le message
            $stmt = $pdo->prepare("INSERT INTO negociation_messages (negociation_id, auteur_id, montant_propose, message, statut)
                                   VALUES (:nid, :uid, :montant, :msg, 'en_attente')");
            $stmt->execute([
                ':nid'     => $nego_id,
                ':uid'     => $uid,
                ':montant' => $montant,
                ':msg'     => $message,
            ]);

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
        }
        break;

    case 'respond':
        // Réponse du vendeur (accepter/refuser)
        $message_id = (int)($_POST['message_id'] ?? 0);
        $response = $_POST['response'] ?? '';

        if (!in_array($response, ['accepte', 'refuse'])) {
            echo json_encode(['success' => false, 'message' => 'Réponse invalide.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE negociation_messages SET statut = :statut WHERE id = :id");
            $stmt->execute([':statut' => $response, ':id' => $message_id]);

            // Si accepté, mettre à jour le statut de la négociation
            if ($response === 'accepte') {
                $stmt = $pdo->prepare("SELECT negociation_id FROM negociation_messages WHERE id = :id");
                $stmt->execute([':id' => $message_id]);
                $msg = $stmt->fetch();
                if ($msg) {
                    $stmt = $pdo->prepare("UPDATE negociations SET statut = 'accepte' WHERE id = :id");
                    $stmt->execute([':id' => $msg['negociation_id']]);
                }
            }

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
}
